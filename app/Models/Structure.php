<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\AccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lieu qui abrite une offre de l'EBJA. Le code (10 chiffres) n'est jamais
 * modifiable par un formulaire : il est généré, puis remplacé uniquement par
 * une recodification. La suppression est logique (archivage).
 */
class Structure extends Model
{
    use Auditable, SoftDeletes;

    public const ETATS = ['EN_ATTENTE', 'VALIDE', 'OUVERT', 'GELE', 'FERME'];

    /** Champs modifiables par formulaire (le code n'y figure pas). */
    protected $fillable = [
        'nom_structure', 'type_offre_id', 'region', 'departement', 'commune', 'quartier', 'ia', 'ief',
        'latitude', 'longitude', 'langue_nationale', 'operateur_id', 'etat',
    ];

    public function typeOffre(): BelongsTo
    {
        return $this->belongsTo(TypeOffre::class);
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(Operateur::class);
    }

    public function ancienne(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ancienne_structure_id')->withTrashed();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function apprenants(): HasMany
    {
        return $this->hasMany(Apprenant::class);
    }

    public function facilitateurs(): HasMany
    {
        return $this->hasMany(Facilitateur::class);
    }

    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return AccessScope::pour($user)->filtrerStructures($query);
    }
}
