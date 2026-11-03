<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Invitation envoyée à la création d'un compte, avec le mot de passe temporaire. */
class InvitationMail extends Mailable
{
    public function __construct(public User $user, public string $motDePasse)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenue sur la plateforme '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invitation', with: ['lien' => route('login')]);
    }
}
