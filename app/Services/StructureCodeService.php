<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Structure;
use App\Models\TypeOffre;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Codification des structures et des classes, et recodification (RM-03 à RM-05).
 */
class StructureCodeService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** RM-03 : « 4 » + 8 chiffres aléatoires + chiffre du type d'offre, unique. */
    public function genererCode(int $typeOffreId): string
    {
        $suffixe = TypeOffre::find($typeOffreId)?->suffixe();
        if ($suffixe === null) {
            throw new RuntimeException("Le type d'offre choisi n'a pas de code valide dans le référentiel.");
        }

        do {
            $milieu = str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
            $code = self::construireCode($milieu, $suffixe);
        } while (Structure::withTrashed()->where('code_structure', $code)->exists());

        return $code;
    }

    public static function construireCode(string $milieu, string $suffixe): string
    {
        return '4'.$milieu.$suffixe;
    }

    public static function estCodeValide(string $code): bool
    {
        return (bool) preg_match('/^4\d{8}[1-9]$/', $code);
    }

    /** RM-05 : {code de la structure}-{préfixe du type d'offre}{numéro d'ordre}. */
    public function genererCodeClasse(Structure $structure): string
    {
        $prefixe = strtoupper(str_replace(' ', '-', trim((string) ($structure->typeOffre?->prefixe ?: 'CLASSE'))));
        $numero = Classe::where('structure_id', $structure->id)->count() + 1;
        do {
            $code = "{$structure->code_structure}-{$prefixe}{$numero}";
            $numero++;
        } while (Classe::where('code_classe', $code)->exists());

        return $code;
    }

    /** Un changement de commune ou de type d'offre impose une recodification (RM-04). */
    public static function exigeRecodification(Structure $structure, array $donnees): bool
    {
        $commune = mb_strtoupper(trim((string) ($donnees['commune'] ?? $structure->commune)));

        return $commune !== mb_strtoupper(trim((string) $structure->commune))
            || (int) ($donnees['type_offre_id'] ?? $structure->type_offre_id) !== (int) $structure->type_offre_id;
    }

    /**
     * RM-04 : crée une nouvelle structure (nouveau code, référence de l'ancienne),
     * y rattache les classes, les facilitateurs et les apprenants, puis archive
     * l'ancienne, qui conserve son code. Tout ou rien (transaction).
     */
    public function recodifier(Structure $ancienne, array $donnees): Structure
    {
        return DB::transaction(function () use ($ancienne, $donnees) {
            $nouvelle = new Structure(array_merge($ancienne->only($ancienne->getFillable()), $donnees));
            $nouvelle->code_structure = $this->genererCode((int) $nouvelle->type_offre_id);
            $nouvelle->ancienne_structure_id = $ancienne->id;
            $nouvelle->save();

            foreach (['classes', 'facilitateur', 'apprenants'] as $table) {
                DB::table($table)->where('structure_id', $ancienne->id)->update(['structure_id' => $nouvelle->id]);
            }
            DB::table('users')->where('structure_id', $ancienne->id)->update(['structure_id' => $nouvelle->id]);

            $ancienne->delete();

            $this->audit->log('RECODIFICATION', 'structures', $nouvelle->id,
                ['code_structure' => $ancienne->code_structure, 'id' => $ancienne->id],
                ['code_structure' => $nouvelle->code_structure, 'id' => $nouvelle->id]);

            return $nouvelle;
        });
    }
}
