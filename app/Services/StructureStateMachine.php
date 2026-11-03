<?php

namespace App\Services;

/**
 * Transitions autorisées entre les états d'une structure (diagramme d'états).
 * L'archivage n'est pas un état choisi par l'agent : il résulte d'une
 * recodification ou d'une suppression.
 */
class StructureStateMachine
{
    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'EN_ATTENTE' => ['VALIDE', 'FERME'],
        'VALIDE' => ['OUVERT', 'FERME'],
        'OUVERT' => ['GELE', 'FERME'],
        'GELE' => ['OUVERT', 'FERME'],
        'FERME' => [],
    ];

    public const LIBELLES = [
        'EN_ATTENTE' => 'En attente de validation',
        'VALIDE' => 'Validée',
        'OUVERT' => 'Ouverte',
        'GELE' => 'Gelée',
        'FERME' => 'Fermée',
    ];

    public static function autorise(string $depuis, string $vers): bool
    {
        return $depuis === $vers || in_array($vers, self::TRANSITIONS[$depuis] ?? [], true);
    }

    /** États proposés dans le formulaire pour une structure dans l'état donné. */
    public static function suivants(?string $depuis): array
    {
        if ($depuis === null) {
            return ['EN_ATTENTE'];
        }

        return array_values(array_unique([$depuis, ...(self::TRANSITIONS[$depuis] ?? [])]));
    }
}
