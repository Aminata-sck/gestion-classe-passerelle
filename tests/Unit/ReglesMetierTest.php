<?php

namespace Tests\Unit;

use App\Models\Apprenant;
use App\Models\Evaluation;
use App\Models\Structure;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use App\Services\SelectionService;
use App\Services\StructureCodeService;
use App\Services\StructureStateMachine;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** Tests unitaires des règles métier : aucune base de données, aucun serveur web. */
#[TestDox('Règles métier')]
class ReglesMetierTest extends TestCase
{
    private function enfant(int $id, string $sexe, int $ans, string $situation = 'Descolarise', string $handicap = 'non', string $famille = '', int $mois = 3): Apprenant
    {
        $a = new Apprenant(['prenom' => "E{$id}", 'nom' => 'Test', 'sexe' => $sexe, 'situation' => $situation, 'handicap' => $handicap, 'situation_familiale' => $famille,
            'date_naissance' => Carbon::create(2026, 9, 1)->subYears($ans)->subMonths($mois)->format('Y-m-d')]);
        $a->id = $id;

        return $a;
    }

    #[Test]
    #[TestDox("La moyenne respecte la formule de RM06")]
    public function la_moyenne_respecte_la_formule_rm06(): void
    {
        $this->assertSame(10.25, Evaluation::calculerMoyenne(9, 11, 10.5));
        $this->assertSame(20.0, Evaluation::calculerMoyenne(20, 20, 20));
        $this->assertSame(0.0, Evaluation::calculerMoyenne(0, 0, 0));
        $this->assertSame(12.42, Evaluation::calculerMoyenne(12.33, 12.33, 12.5)); // arrondi au centième
    }

    #[Test]
    #[TestDox("Le code de structure respecte le format de RM03")]
    public function le_code_de_structure_respecte_le_format_rm03(): void
    {
        $code = StructureCodeService::construireCode('12345678', '3');
        $this->assertSame('4123456783', $code);
        $this->assertTrue(StructureCodeService::estCodeValide($code));
        $this->assertFalse(StructureCodeService::estCodeValide('5123456783'), 'premier chiffre différent de 4');
        $this->assertFalse(StructureCodeService::estCodeValide('412345678'), 'neuf chiffres');
        $this->assertFalse(StructureCodeService::estCodeValide('4123456780'), 'suffixe nul');
    }

    #[Test]
    #[TestDox("Un changement de commune ou de type impose une recodification")]
    public function un_changement_de_commune_ou_de_type_impose_une_recodification(): void
    {
        $s = new Structure(['commune' => 'Thiaroye', 'type_offre_id' => 2]);
        $this->assertFalse(StructureCodeService::exigeRecodification($s, ['commune' => ' thiaroye ', 'type_offre_id' => 2]));
        $this->assertTrue(StructureCodeService::exigeRecodification($s, ['commune' => 'Pikine', 'type_offre_id' => 2]));
        $this->assertTrue(StructureCodeService::exigeRecodification($s, ['commune' => 'Thiaroye', 'type_offre_id' => 4]));
    }

    #[Test]
    #[TestDox("Seules les transitions prévues sont autorisées")]
    public function seules_les_transitions_prevues_sont_autorisees(): void
    {
        $this->assertTrue(StructureStateMachine::autorise('EN_ATTENTE', 'VALIDE'));
        $this->assertTrue(StructureStateMachine::autorise('OUVERT', 'GELE'));
        $this->assertTrue(StructureStateMachine::autorise('GELE', 'OUVERT'));
        $this->assertFalse(StructureStateMachine::autorise('FERME', 'OUVERT'));
        $this->assertFalse(StructureStateMachine::autorise('EN_ATTENTE', 'OUVERT'));
        $this->assertSame(['EN_ATTENTE'], StructureStateMachine::suivants(null));
    }

    #[Test]
    #[TestDox("Le périmètre filtre selon le niveau (IEF homonymes compris)")]
    public function le_perimetre_filtre_selon_le_niveau(): void
    {
        $pikine = new Structure(['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine']);
        $pikine->id = 1;
        $homonyme = new Structure(['ia' => 'IA Autre', 'ief' => 'IEF Pikine']);
        $homonyme->id = 2;

        $agentIef = new AccessScope('IEF', 'IA PIKINE GUEDIAWAYE', 'IEF PIKINE');
        $this->assertTrue($agentIef->autoriseStructure($pikine));
        $this->assertFalse($agentIef->autoriseStructure($homonyme), 'IEF homonyme dans une autre académie');

        $agentIa = new AccessScope('IA', 'IA PIKINE GUEDIAWAYE');
        $this->assertTrue($agentIa->autoriseStructure($pikine));
        $this->assertFalse($agentIa->autoriseStructure($homonyme));

        $this->assertTrue((new AccessScope('NATIONAL'))->autoriseStructure($homonyme));
        $this->assertTrue((new AccessScope('STRUCTURE', null, null, 1))->autoriseStructure($pikine));
        $this->assertFalse((new AccessScope('STRUCTURE', null, null, 1))->autoriseStructure($homonyme));
        $this->assertFalse((new AccessScope('INCONNU'))->autoriseStructure($pikine), 'refus par défaut');
    }

    #[Test]
    #[TestDox("Un rattachement incomplet est refusé")]
    public function un_rattachement_incomplet_est_refuse(): void
    {
        $this->assertTrue((new AccessScope('NATIONAL'))->estComplet());
        $this->assertFalse((new AccessScope('IEF', 'IA DAKAR', null))->estComplet());
        $this->assertFalse((new AccessScope('IA'))->estComplet());
        $this->assertFalse((new AccessScope('STRUCTURE'))->estComplet());
        $this->assertFalse((new AccessScope(null))->estComplet());
        $this->assertSame('IEF PIKINE', AccessScope::normaliser('  IEF Pikine '));
    }

    #[Test]
    #[TestDox("L'éligibilité respecte les bornes de RM01")]
    public function l_eligibilite_respecte_les_bornes_de_rm01(): void
    {
        $date = Carbon::create(2026, 9, 1);
        $r = (new SelectionService)->classer([
            $this->enfant(1, 'M', 8, mois: 0), $this->enfant(2, 'M', 14, mois: 11), $this->enfant(3, 'M', 7, mois: 11),
            $this->enfant(4, 'M', 15, mois: 0), $this->enfant(5, 'M', 10, 'Scolarise'),
        ], 10, $date);

        $this->assertEqualsCanonicalizing([1, 2], array_column($r['retenus'], 'id'));
        $this->assertEqualsCanonicalizing([3, 4, 5], array_column($r['ecartes'], 'id'));
        $this->assertSame('Situation scolaire non éligible', collect($r['ecartes'])->firstWhere('id', 5)['motif']);
    }

    #[Test]
    #[TestDox("Le départage suit l'ordre de RM02")]
    public function le_departage_suit_l_ordre_de_rm02(): void
    {
        $r = (new SelectionService)->classer([
            $this->enfant(1, 'M', 12),                          // non vulnérable, garçon, 12 ans
            $this->enfant(2, 'F', 12),                          // non vulnérable, fille
            $this->enfant(3, 'M', 9, famille: 'Orphelin'),      // vulnérable
            $this->enfant(4, 'M', 13, handicap: 'oui'),         // vulnérable, plus proche de 12 ans
            $this->enfant(5, 'M', 9),                           // non vulnérable, loin de 12 ans
        ], 3, Carbon::create(2026, 9, 1));

        $ordre = array_column(array_merge($r['retenus'], $r['attente']), 'id');
        $this->assertSame([4, 3, 2, 1, 5], $ordre);
        $this->assertCount(3, $r['retenus']);
        $this->assertSame([1, 5], array_column($r['attente'], 'id'));
    }

    #[Test]
    #[TestDox("Les champs sensibles sont masqués dans le journal")]
    public function les_champs_sensibles_sont_masques_dans_le_journal(): void
    {
        $m = AuditLogger::masquer(['email' => 'a@b.sn', 'password' => 'secret', 'remember_token' => 'x']);
        $this->assertSame('a@b.sn', $m['email']);
        $this->assertSame(AuditLogger::MASQUE, $m['password']);
        $this->assertSame(AuditLogger::MASQUE, $m['remember_token']);
    }
}
