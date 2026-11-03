<?php

namespace App\Services;

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Périmètre territorial d'un utilisateur (règle RM-07).
 *
 * Le profil (rôle) fixe les actions permises ; le périmètre fixe les données
 * accessibles : national, une académie (IA), une inspection (IEF) ou une seule
 * structure. Le filtrage est appliqué dans les requêtes : une donnée hors
 * périmètre n'est jamais envoyée au navigateur.
 */
class AccessScope
{
    public const NATIONAL = 'NATIONAL';
    public const IA = 'IA';
    public const IEF = 'IEF';
    public const STRUCTURE = 'STRUCTURE';

    public function __construct(
        public readonly ?string $niveau,
        public readonly ?string $ia = null,
        public readonly ?string $ief = null,
        public readonly ?int $structureId = null,
    ) {
    }

    public static function pour(User $user): self
    {
        return new self(
            strtoupper((string) $user->niveau_acces) ?: null,
            self::normaliser($user->ia),
            self::normaliser($user->ief),
            $user->structure_id ? (int) $user->structure_id : null,
        );
    }

    /** Un rattachement incomplet ne permet pas de se connecter (refus par défaut). */
    public function estComplet(): bool
    {
        return match ($this->niveau) {
            self::NATIONAL => true,
            self::IA => $this->ia !== null,
            self::IEF => $this->ia !== null && $this->ief !== null,
            self::STRUCTURE => $this->structureId !== null,
            default => false,
        };
    }

    public function estNational(): bool
    {
        return $this->niveau === self::NATIONAL;
    }

    /** Restreint une requête sur la table des structures au périmètre. */
    public function filtrerStructures(Builder|QueryBuilder $query, string $table = 'structures'): Builder|QueryBuilder
    {
        return match ($this->niveau) {
            self::NATIONAL => $query,
            self::IA => $query->whereRaw("UPPER(TRIM({$table}.ia)) = ?", [$this->ia]),
            self::IEF => $query->whereRaw("UPPER(TRIM({$table}.ia)) = ?", [$this->ia])
                ->whereRaw("UPPER(TRIM({$table}.ief)) = ?", [$this->ief]),
            self::STRUCTURE => $query->where("{$table}.id", $this->structureId),
            default => $query->whereRaw('1 = 0'), // niveau inconnu : aucune donnée
        };
    }

    /** Restreint une requête dont les lignes portent une colonne structure_id. */
    public function filtrerParStructure(Builder|QueryBuilder $query, string $colonne = 'structure_id'): Builder|QueryBuilder
    {
        if ($this->estNational()) {
            return $query;
        }

        return $query->whereIn($colonne, function (QueryBuilder $sub) {
            $sub->select('structures.id')->from('structures')->whereNull('structures.deleted_at');
            $this->filtrerStructures($sub);
        });
    }

    /** Décide pour une structure précise (accès direct par l'adresse). */
    public function autoriseStructure(?Structure $structure): bool
    {
        if ($structure === null) {
            return $this->estNational();
        }

        return match ($this->niveau) {
            self::NATIONAL => true,
            self::IA => self::normaliser($structure->ia) === $this->ia,
            self::IEF => self::normaliser($structure->ia) === $this->ia
                && self::normaliser($structure->ief) === $this->ief,
            self::STRUCTURE => (int) $structure->id === $this->structureId,
            default => false,
        };
    }

    public static function normaliser(?string $valeur): ?string
    {
        $valeur = trim((string) $valeur);

        return $valeur === '' ? null : mb_strtoupper($valeur);
    }
}
