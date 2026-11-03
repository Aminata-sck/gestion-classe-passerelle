<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Apprenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Sélection et comptes')]
class SelectionEtComptesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referentiels();
    }

    #[Test]
    #[TestDox("La sélection classe les enfants de la structure")]
    public function la_selection_classe_les_enfants_de_la_structure(): void
    {
        $s = $this->structure('IA Pikine Guediawaye', 'IEF Pikine');
        $naissance = fn (int $ans) => Carbon::create(2026, 9, 1)->subYears($ans)->subMonths(3)->format('Y-m-d');
        Apprenant::create(['nom' => 'A', 'prenom' => 'Garçon', 'sexe' => 'M', 'situation' => 'Descolarise', 'date_naissance' => $naissance(12), 'structure_id' => $s->id]);
        Apprenant::create(['nom' => 'B', 'prenom' => 'Fille', 'sexe' => 'F', 'situation' => 'Non_scolarise', 'date_naissance' => $naissance(12), 'structure_id' => $s->id]);
        Apprenant::create(['nom' => 'C', 'prenom' => 'Trop jeune', 'sexe' => 'F', 'situation' => 'Descolarise', 'date_naissance' => $naissance(6), 'structure_id' => $s->id]);

        $this->actingAs($this->utilisateur())->get("/selection?structure_id={$s->id}&places=1&date=2026-09-01")
            ->assertInertia(fn (Page $p) => $p->component('Selection/Index')
                ->where('resultat.retenus.0.nom', 'Fille B')
                ->where('resultat.attente.0.nom', 'Garçon A')
                ->where('resultat.ecartes.0.nom', 'Trop jeune C'));
    }

    #[Test]
    #[TestDox("La sélection d'une structure hors périmètre est refusée")]
    public function la_selection_d_une_structure_hors_perimetre_est_refusee(): void
    {
        $s = $this->structure('IA THIES', 'IEF Mbour 1');
        $agent = $this->utilisateur('IEF', ['ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine'], 2);
        $this->actingAs($agent)->get("/selection?structure_id={$s->id}")->assertNotFound();
    }

    #[Test]
    #[TestDox("Un compte créé reçoit un mot de passe temporaire par courriel")]
    public function un_compte_cree_recoit_un_mot_de_passe_temporaire_par_courriel(): void
    {
        Mail::fake();
        $this->actingAs($this->utilisateur())->post('/users', [
            'nom' => 'Ndiaye', 'prenom' => 'Awa', 'email' => 'Awa.Ndiaye@Exemple.TEST', 'role_id' => 2,
            'niveau_acces' => 'IEF', 'ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine',
        ])->assertRedirect();

        $u = User::firstWhere('email', 'awa.ndiaye@exemple.test');
        $this->assertTrue($u->must_change_password);
        Mail::assertSent(InvitationMail::class, function (InvitationMail $m) use ($u) {
            return $m->hasTo($u->email) && Hash::check($m->motDePasse, $u->password);
        });
    }

    #[Test]
    #[TestDox("Un compte mal rattaché n'est pas créé")]
    public function un_compte_mal_rattache_n_est_pas_cree(): void
    {
        $this->actingAs($this->utilisateur())->post('/users', [
            'nom' => 'Sarr', 'email' => 'sarr@exemple.test', 'role_id' => 2, 'niveau_acces' => 'IEF', 'ia' => 'IA DAKAR',
        ])->assertSessionHasErrors();
        $this->assertDatabaseMissing('users', ['email' => 'sarr@exemple.test']);
    }

    #[Test]
    #[TestDox("Les messages de validation sont en français")]
    public function les_messages_de_validation_sont_en_francais(): void
    {
        $this->actingAs($this->utilisateur())->post('/users', [])
            ->assertSessionHasErrors(['nom' => 'Le champ nom est obligatoire.', 'role_id' => 'Le champ profil est obligatoire.']);
    }

    #[Test]
    #[TestDox("La modification des droits d'un profil est journalisée")]
    public function la_modification_des_droits_d_un_profil_est_journalisee(): void
    {
        $this->actingAs($this->utilisateur())->put('/profils/3', ['nom' => 'Agent de structure', 'droits' => ['1:1:1', '5:13:1']])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'DROITS_MODIFIES', 'entity' => 'roles', 'entity_id' => '3']);
    }
}
