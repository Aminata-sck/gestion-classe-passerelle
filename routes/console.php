<?php

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * Commandes d'exploitation.
 *   php artisan motdepasse admin@exemple.sn    → mot de passe temporaire, changement imposé
 *   php artisan compte:admin admin@exemple.sn "Nom"  → premier administrateur national
 */
Artisan::command('motdepasse {email}', function (string $email) {
    $user = User::where('email', Str::lower($email))->first();
    if (! $user) {
        $this->error("Aucun compte pour {$email}.");

        return 1;
    }
    $motDePasse = Str::password(14, symbols: false);
    $user->forceFill(['password' => $motDePasse, 'must_change_password' => true, 'status' => true])->save();
    app(AuditLogger::class)->log('MOT_DE_PASSE_REINITIALISE', 'users', $user->id, null, ['source' => 'console']);
    $this->info("Mot de passe temporaire de {$user->email} : {$motDePasse}");
    $this->line('Il devra être changé à la première connexion.');

    return 0;
})->purpose('Réinitialise le mot de passe d\'un compte (accès serveur requis)');

Artisan::command('compte:admin {email} {nom}', function (string $email, string $nom) {
    if (User::where('email', Str::lower($email))->exists()) {
        $this->error('Ce compte existe déjà ; utilisez « php artisan motdepasse ».');

        return 1;
    }
    $motDePasse = Str::password(14, symbols: false);
    User::create(['nom' => $nom, 'email' => Str::lower($email), 'password' => $motDePasse, 'role_id' => 1,
        'niveau_acces' => 'NATIONAL', 'status' => true, 'must_change_password' => true]);
    $this->info("Administrateur créé. Mot de passe temporaire : {$motDePasse}");

    return 0;
})->purpose('Crée le premier administrateur de niveau national');
