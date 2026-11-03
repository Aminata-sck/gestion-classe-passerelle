<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Périmètre et droits')]
class PerimetreEtDroitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referentiels();
    }

    #[Test]
    #[TestDox("Un agent ne liste que les structures de son IEF")]
    public function un_agent_ne_liste_que_les_structures_de_son_ief(): void
    {
        $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $this->structure('IA THIES', 'IEF Mbour 1');
        $this->structure('IA AUTRE', 'IEF Pikine'); // homonyme dans une autre académie
        $agent = $this->utilisateur('IEF', ['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine'], 2);

        $this->actingAs($agent)->get('/structures')->assertInertia(fn (Page $p) => $p
            ->component('Structures/Index')
            ->where('compteurs.total', 1)
            ->has('structures.data', 1)
            ->where('structures.data.0.ief', 'IEF Pikine')
            ->where('structures.data.0.ia', 'IA Pikine Guediawaye'));
    }

    #[Test]
    #[TestDox("Un accès direct hors périmètre est refusé et journalisé")]
    public function l_acces_direct_a_une_structure_hors_perimetre_est_refuse_et_journalise(): void
    {
        $mbour = $this->structure('IA THIES', 'IEF Mbour 1');
        $agent = $this->utilisateur('IEF', ['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine'], 2);

        $this->actingAs($agent)->get("/structures/{$mbour->id}")->assertNotFound();
        $this->actingAs($agent)->put("/structures/{$mbour->id}", ['nom_structure' => 'X'])->assertNotFound();
        $this->assertSame(2, AuditLog::where('action', 'ACCES_REFUSE')->where('entity_id', (string) $mbour->id)->count());
    }

    #[Test]
    #[TestDox("Le niveau national voit toutes les structures")]
    public function le_niveau_national_voit_tout(): void
    {
        $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $this->structure('IA THIES', 'IEF Mbour 1');
        $this->actingAs($this->utilisateur())->get('/structures')->assertInertia(fn (Page $p) => $p->where('compteurs.total', 2));
    }

    #[Test]
    #[TestDox("Une route sans le droit requis est refusée")]
    public function une_route_sans_le_droit_requis_est_refusee(): void
    {
        $agent = $this->utilisateur('IEF', ['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine'], 2);
        $this->actingAs($agent)->get('/users')->assertForbidden();
        $this->actingAs($agent)->get('/journal')->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'ACCES_REFUSE', 'entity' => 'route']);
    }

    #[Test]
    #[TestDox("Un agent ne crée pas de structure hors de son périmètre")]
    public function un_agent_ne_peut_pas_creer_une_structure_hors_de_son_perimetre(): void
    {
        $agent = $this->utilisateur('IEF', ['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine'], 2);
        $this->actingAs($agent)->post('/structures', [
            'nom_structure' => 'Hors périmètre', 'type_offre_id' => 2, 'region' => 'THIES', 'departement' => 'Mbour', 'commune' => 'Mbour',
            'quartier' => 'Centre', 'ia' => 'IA THIES', 'ief' => 'IEF Mbour 1', 'langue_nationale' => 'Wolof', 'etat' => 'EN_ATTENTE',
        ])->assertSessionHasErrors('ief');
        $this->assertDatabaseMissing('structures', ['nom_structure' => 'Hors périmètre']);
    }

    #[Test]
    #[TestDox("Un identifiant inexistant renvoie « introuvable »")]
    public function un_identifiant_inexistant_renvoie_introuvable_meme_au_niveau_national(): void
    {
        $admin = $this->utilisateur();
        $this->actingAs($admin)->get('/structures/999999')->assertNotFound();
        $this->actingAs($admin)->get('/structures/999999/edit')->assertNotFound();
        $this->actingAs($admin)->get('/apprenant/999999')->assertNotFound();
        $this->actingAs($admin)->get('/selection?structure_id=999999')->assertNotFound();
    }

    #[Test]
    #[TestDox("Les anciennes adresses du menu mènent aux nouveaux écrans")]
    public function les_anciennes_adresses_sont_redirigees(): void
    {
        $admin = $this->utilisateur();
        $this->actingAs($admin)->get('/audit')->assertRedirect('/journal');
        $this->actingAs($admin)->get('/apprenant/selection')->assertRedirect('/selection');
        \Illuminate\Support\Facades\DB::table('sous_menus')->where('id', 41)->update(['url' => 'audit']);
        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Page $p) => $p
            ->where('menu', fn ($menu) => collect($menu)->pluck('items')->flatten(1)->contains(fn ($i) => $i['url'] === '/journal')));
    }
}
