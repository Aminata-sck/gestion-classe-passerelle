<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\AuditLog;
use App\Models\Classe;
use App\Models\Structure;
use App\Services\StructureCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Structures et classes')]
class StructuresEtClassesTest extends TestCase
{
    use RefreshDatabase;

    private array $donnees = [
        'nom_structure' => 'Classe passerelle de Test', 'type_offre_id' => 2, 'region' => 'DAKAR', 'departement' => 'Pikine',
        'commune' => 'Thiaroye', 'quartier' => 'Centre', 'ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine',
        'langue_nationale' => 'Wolof', 'etat' => 'EN_ATTENTE',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->referentiels();
        $this->actingAs($this->utilisateur());
    }

    #[Test]
    #[TestDox("La création génère un code conforme et ignore un code saisi")]
    public function la_creation_genere_un_code_conforme_et_ignore_un_code_saisi(): void
    {
        $this->post('/structures', $this->donnees + ['code_structure' => '4000000000'])->assertRedirect();
        $s = Structure::firstWhere('nom_structure', 'Classe passerelle de Test');
        $this->assertTrue(StructureCodeService::estCodeValide($s->code_structure));
        $this->assertStringEndsWith('3', $s->code_structure, 'suffixe du type « classe passerelle »');
        $this->assertNotSame('4000000000', $s->code_structure);
    }

    #[Test]
    #[TestDox("Le code ne peut pas être modifié par formulaire")]
    public function le_code_ne_peut_pas_etre_modifie_par_formulaire(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $code = $s->code_structure;
        $this->put("/structures/{$s->id}", ['code_structure' => '4111111113', 'nom_structure' => 'Nouveau nom'] + $this->donnees)->assertRedirect();
        $this->assertSame($code, $s->fresh()->code_structure);
        $this->assertSame('Nouveau nom', $s->fresh()->nom_structure);
    }

    #[Test]
    #[TestDox("Un changement de commune recodifie et archive l'ancienne")]
    public function un_changement_de_commune_recodifie_et_archive_l_ancienne_structure(): void
    {
        $ancienne = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $classe = new Classe(['nom_classe' => 'A', 'structure_id' => $ancienne->id, 'niveau' => 'CI']);
        $classe->code_classe = app(StructureCodeService::class)->genererCodeClasse($ancienne);
        $classe->save();
        $enfant = Apprenant::create(['nom' => 'Diop', 'prenom' => 'Awa', 'sexe' => 'F', 'structure_id' => $ancienne->id]);

        $this->put("/structures/{$ancienne->id}", ['commune' => 'Guinaw Rails'] + $this->donnees)->assertRedirect();

        $this->assertSoftDeleted('structures', ['id' => $ancienne->id, 'code_structure' => $ancienne->code_structure]);
        $nouvelle = Structure::where('ancienne_structure_id', $ancienne->id)->firstOrFail();
        $this->assertNotSame($ancienne->code_structure, $nouvelle->code_structure);
        $this->assertSame('Guinaw Rails', $nouvelle->commune);
        $this->assertSame($nouvelle->id, $classe->fresh()->structure_id);
        $this->assertSame($nouvelle->id, $enfant->fresh()->structure_id);
        $this->assertSame($classe->code_classe, $classe->fresh()->code_classe, 'le code de classe ne change jamais');
        $this->assertTrue(AuditLog::where('action', 'RECODIFICATION')->exists());
    }

    #[Test]
    #[TestDox("Une transition d'état non prévue est refusée")]
    public function une_transition_d_etat_non_prevue_est_refusee(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine', ['etat' => 'FERME']);
        $this->put("/structures/{$s->id}", ['etat' => 'OUVERT'] + $this->donnees)->assertSessionHasErrors('etat');
        $this->assertSame('FERME', $s->fresh()->etat);
    }

    #[Test]
    #[TestDox("Le code de classe est généré et stable")]
    public function le_code_de_classe_est_genere_et_stable(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $this->post('/classes', ['nom_classe' => 'Étape 1', 'structure_id' => $s->id, 'niveau' => 'CI'])->assertRedirect();
        $this->post('/classes', ['nom_classe' => 'Étape 2', 'structure_id' => $s->id, 'niveau' => 'CP'])->assertRedirect();
        $c = Classe::firstWhere('nom_classe', 'Étape 1');
        $this->assertSame("{$s->code_structure}-CLASSE-PASSERELLE1", $c->code_classe);
        $this->assertSame("{$s->code_structure}-CLASSE-PASSERELLE2", Classe::firstWhere('nom_classe', 'Étape 2')->code_classe);

        $this->put("/classes/{$c->id}", ['nom_classe' => 'Renommée', 'structure_id' => $s->id, 'niveau' => 'CI'])->assertRedirect();
        $this->assertSame("{$s->code_structure}-CLASSE-PASSERELLE1", $c->fresh()->code_classe);
    }

    #[Test]
    #[TestDox("Une modification est historisée (valeurs avant/après)")]
    public function la_modification_est_historisee_avec_les_valeurs_avant_et_apres(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $this->put("/structures/{$s->id}", ['nom_structure' => 'Nom corrigé'] + $this->donnees);
        $log = AuditLog::where('action', 'MODIFICATION')->where('entity', 'structures')->latest('id')->firstOrFail();
        $this->assertSame('Structure IEF Pikine', $log->old_values['nom_structure']);
        $this->assertSame('Nom corrigé', $log->new_values['nom_structure']);
    }
}
