<!doctype html>
<html lang="fr"><body style="font-family:Arial,sans-serif;color:#1f2a2e">
<p>Bonjour {{ $user->prenom }} {{ $user->nom }},</p>
<p>Un compte vous a été créé sur la plateforme <strong>{{ config('app.name') }}</strong>.</p>
<p>Identifiant : <strong>{{ $user->email }}</strong><br>Mot de passe temporaire : <strong>{{ $motDePasse }}</strong></p>
<p>Ce mot de passe devra être remplacé lors de votre première connexion.</p>
<p><a href="{{ $lien }}">Se connecter</a></p>
</body></html>
