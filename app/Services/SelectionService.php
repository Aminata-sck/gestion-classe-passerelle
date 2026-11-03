<?php

namespace App\Services;

use App\Models\Apprenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Classement de sélection des apprenants (règles RM-01 et RM-02).
 *
 * RM-01 : éligible si 8 à 14 ans à la date de référence et déscolarisé ou non scolarisé.
 * RM-02 : à critères égaux, priorité à l'enfant vulnérable, puis aux filles, puis
 *         à l'enfant le plus proche de 12 ans (avant 12 ans le plus âgé, après 12
 *         ans le moins âgé), enfin à l'ordre d'inscription pour un résultat stable.
 * Le classement est recalculé à chaque demande et ne modifie aucune donnée.
 */
class SelectionService
{
    public const AGE_MIN = 8;
    public const AGE_MAX = 14;
    public const AGE_PIVOT = 12;

    /**
     * @param  iterable<Apprenant>  $apprenants
     * @return array{retenus: list<array>, attente: list<array>, ecartes: list<array>}
     */
    public function classer(iterable $apprenants, int $places, CarbonInterface $dateReference): array
    {
        $eligibles = [];
        $ecartes = [];

        foreach ($apprenants as $apprenant) {
            $ligne = $this->ligne($apprenant, $dateReference);
            $motif = $this->motifExclusion($apprenant, $ligne['age']);
            if ($motif !== null) {
                $ecartes[] = $ligne + ['motif' => $motif];

                continue;
            }
            $eligibles[] = $ligne;
        }

        usort($eligibles, static function (array $a, array $b): int {
            // 1. vulnérabilité  2. fille  3. proximité de 12 ans  4. ordre d'inscription
            return [$b['vulnerable'], $b['sexe'] === 'F', $a['distance'], $a['id']]
                <=> [$a['vulnerable'], $a['sexe'] === 'F', $b['distance'], $b['id']];
        });

        $places = max(0, $places);
        $rang = 0;
        $eligibles = array_map(static function (array $l) use (&$rang) {
            $l['rang'] = ++$rang;

            return $l;
        }, $eligibles);

        return [
            'retenus' => array_slice($eligibles, 0, $places),
            'attente' => array_slice($eligibles, $places),
            'ecartes' => $ecartes,
        ];
    }

    public function motifExclusion(Apprenant $apprenant, ?float $age): ?string
    {
        if ($age === null) {
            return 'Date de naissance inconnue';
        }
        $ans = (int) floor($age);
        if ($ans < self::AGE_MIN) {
            return "Moins de 8 ans ({$ans} ans)";
        }
        if ($ans > self::AGE_MAX) {
            return "Plus de 14 ans ({$ans} ans)";
        }
        if (! in_array($apprenant->situation, ['Descolarise', 'Non_scolarise'], true)) {
            return 'Situation scolaire non éligible';
        }

        return null;
    }

    private function ligne(Apprenant $apprenant, CarbonInterface $date): array
    {
        $age = $apprenant->date_naissance ? $apprenant->date_naissance->diffInDays($date) / 365.25 : null;

        return [
            'id' => (int) $apprenant->id,
            'nom' => trim($apprenant->prenom.' '.$apprenant->nom),
            'sexe' => (string) $apprenant->sexe,
            'age' => $age === null ? null : round($age, 2),
            'age_ans' => $age === null ? null : (int) floor($age),
            'situation' => Apprenant::SITUATIONS[$apprenant->situation] ?? '—',
            'vulnerable' => $apprenant->estVulnerable(),
            'distance' => $age === null ? 99.0 : abs($age - self::AGE_PIVOT),
        ];
    }
}
