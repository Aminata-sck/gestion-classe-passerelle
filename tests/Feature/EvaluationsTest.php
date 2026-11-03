<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Evaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Évaluations')]
class EvaluationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referentiels();
    }

    #[Test]
    #[TestDox("La moyenne est calculée par le serveur")]
    public function la_moyenne_est_calculee_par_le_serveur(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $a = Apprenant::create(['nom' => 'Diop', 'prenom' => 'Fatou', 'sexe' => 'F', 'structure_id' => $s->id]);
        $this->actingAs($this->utilisateur())
            ->post("/apprenant/{$a->id}/evaluations", ['libelle' => 'Mois 1', 'note1' => 9, 'note2' => 11, 'composition' => 10.5, 'moyenne' => 20])
            ->assertRedirect();
        $this->assertSame(10.25, Evaluation::firstOrFail()->moyenne);
    }

    #[Test]
    #[TestDox("Une note hors de l'intervalle 0-20 est refusée")]
    public function une_note_hors_de_l_intervalle_est_refusee(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $a = Apprenant::create(['nom' => 'Diop', 'prenom' => 'Fatou', 'sexe' => 'F', 'structure_id' => $s->id]);
        $this->actingAs($this->utilisateur())
            ->post("/apprenant/{$a->id}/evaluations", ['libelle' => 'Mois 1', 'note1' => 21, 'note2' => 11, 'composition' => -1])
            ->assertSessionHasErrors(['note1', 'composition']);
        $this->assertSame(0, Evaluation::count());
    }

    #[Test]
    #[TestDox("Un agent ne peut pas noter un apprenant hors périmètre")]
    public function un_agent_ne_peut_pas_noter_un_apprenant_hors_perimetre(): void
    {
        $s = $this->structure('IA THIES', 'IEF Mbour 1');
        $a = Apprenant::create(['nom' => 'Faye', 'prenom' => 'Lamine', 'sexe' => 'M', 'structure_id' => $s->id]);
        $agent = $this->utilisateur('IEF', ['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine'], 2);
        $this->actingAs($agent)->post("/apprenant/{$a->id}/evaluations", ['libelle' => 'X', 'note1' => 10, 'note2' => 10, 'composition' => 10])->assertNotFound();
        $this->assertSame(0, Evaluation::count());
    }
}
