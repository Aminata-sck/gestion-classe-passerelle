<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\AccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Personne qui tient une classe. */
class Facilitateur extends Model
{
    use Auditable;

    protected $table = 'facilitateur';

    protected $fillable = ['matricule', 'prenom', 'nom', 'sexe', 'date_naissance', 'telephone', 'email', 'adresse', 'niveau_etude', 'specialite', 'date_recrutement', 'type_contrat', 'structure_id', 'classe_id', 'statut'];

    protected $casts = ['date_naissance' => 'date:Y-m-d', 'date_recrutement' => 'date:Y-m-d'];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return AccessScope::pour($user)->filtrerParStructure($query, 'facilitateur.structure_id');
    }
}
