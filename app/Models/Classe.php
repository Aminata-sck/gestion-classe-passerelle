<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\AccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Groupe d'apprenants d'un niveau. Son code est généré et ne change jamais (RM-05). */
class Classe extends Model
{
    use Auditable;

    public const NIVEAUX = ['CI', 'CP', 'CE1', 'CE2'];

    protected $table = 'classes';

    protected $fillable = ['nom_classe', 'structure_id', 'facilitateur_id', 'niveau', 'langue', 'date_ouverture'];

    protected $casts = ['date_ouverture' => 'date:Y-m-d'];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }

    public function apprenants(): HasMany
    {
        return $this->hasMany(Apprenant::class);
    }

    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return AccessScope::pour($user)->filtrerParStructure($query, 'classes.structure_id');
    }
}
