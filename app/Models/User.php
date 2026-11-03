<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Auditable;

    public const NIVEAUX = ['NATIONAL', 'IA', 'IEF', 'STRUCTURE'];

    protected $fillable = ['nom', 'prenom', 'ine', 'email', 'password', 'role_id', 'status', 'must_change_password', 'niveau_acces', 'ia', 'ief', 'structure_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim(($this->prenom ?? '').' '.$this->nom);
    }
}
