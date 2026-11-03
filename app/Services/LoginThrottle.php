<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Limitation des tentatives de connexion : 5 échecs pour un couple e-mail et
 * adresse IP, ou 20 échecs pour une adresse IP, sur 15 minutes.
 */
class LoginThrottle
{
    public const MAX_COMPTE = 5;
    public const MAX_IP = 20;
    public const DUREE = 15 * 60;

    public function estBloque(string $email, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->cleCompte($email, $ip), self::MAX_COMPTE)
            || RateLimiter::tooManyAttempts($this->cleIp($ip), self::MAX_IP);
    }

    public function echec(string $email, string $ip): void
    {
        RateLimiter::hit($this->cleCompte($email, $ip), self::DUREE);
        RateLimiter::hit($this->cleIp($ip), self::DUREE);
    }

    public function succes(string $email, string $ip): void
    {
        RateLimiter::clear($this->cleCompte($email, $ip));
    }

    public function reinitialiser(string $email): void
    {
        // Utilisé par la commande de réinitialisation : efface les compteurs connus.
        RateLimiter::clear('connexion:'.Str::lower($email));
    }

    private function cleCompte(string $email, string $ip): string
    {
        return 'connexion:'.Str::lower(trim($email)).'|'.$ip;
    }

    private function cleIp(string $ip): string
    {
        return 'connexion-ip:'.$ip;
    }
}
