<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Authentification')]
class AuthentificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referentiels();
    }

    #[Test]
    #[TestDox("Un visiteur est redirigé vers la connexion")]
    public function un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/structures')->assertRedirect('/login');
    }

    #[Test]
    #[TestDox("Une connexion réussie ouvre le tableau de bord et est journalisée")]
    public function la_connexion_reussie_ouvre_le_tableau_de_bord_et_est_journalisee(): void
    {
        $u = $this->utilisateur();
        $this->post('/login', ['email' => $u->email, 'password' => 'MotDePasse2026'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CONNEXION', 'user_id' => $u->id]);
    }

    #[Test]
    #[TestDox("La connexion est suspendue après cinq échecs")]
    public function la_connexion_est_suspendue_apres_cinq_echecs(): void
    {
        $u = $this->utilisateur();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'mauvais'])->assertSessionHasErrors('email');
        }
        // Même le bon mot de passe est refusé pendant la suspension.
        $this->post('/login', ['email' => $u->email, 'password' => 'MotDePasse2026'])->assertSessionHasErrors(['email' => 'Trop de tentatives. Réessayez dans 15 minutes.']);
        $this->assertGuest();
        $this->assertSame(5, AuditLog::where('action', 'CONNEXION_ECHEC')->count());
        $this->assertSame(1, AuditLog::where('action', 'CONNEXION_BLOQUEE')->count());
        $this->assertStringNotContainsString('mauvais', AuditLog::pluck('new_values')->toJson(), 'le mot de passe essayé n’est jamais conservé');
    }

    #[Test]
    #[TestDox("Un compte bloqué ou mal rattaché ne peut pas se connecter")]
    public function un_compte_bloque_ou_mal_rattache_ne_peut_pas_se_connecter(): void
    {
        $bloque = $this->utilisateur('NATIONAL', ['status' => false]);
        $this->post('/login', ['email' => $bloque->email, 'password' => 'MotDePasse2026'])->assertSessionHasErrors('email');
        $incomplet = $this->utilisateur('IEF', ['ia' => 'IA DAKAR'], 2);
        $this->post('/login', ['email' => $incomplet->email, 'password' => 'MotDePasse2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    #[TestDox("Le mot de passe temporaire est changé à la première connexion")]
    public function le_mot_de_passe_temporaire_doit_etre_change_a_la_premiere_connexion(): void
    {
        $u = $this->utilisateur('NATIONAL', ['must_change_password' => true]);
        $this->post('/login', ['email' => $u->email, 'password' => 'MotDePasse2026'])->assertRedirect('/password');
        $this->get('/structures')->assertRedirect('/password');
        $this->put('/password', ['current_password' => 'MotDePasse2026', 'password' => 'NouveauMotDePasse2027', 'password_confirmation' => 'NouveauMotDePasse2027'])->assertRedirect('/dashboard');
        $this->assertFalse($u->fresh()->must_change_password);
        $this->get('/structures')->assertOk();
    }

    #[Test]
    #[TestDox("Les en-têtes de protection sont présents")]
    public function les_en_tetes_de_protection_sont_presents(): void
    {
        $this->get('/login')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Referrer-Policy');
    }
}
