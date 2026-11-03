<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\AccessScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** Enfant enrôlé dans le dispositif. */
class Apprenant extends Model
{
    use Auditable;

    public const SITUATIONS = ['Descolarise' => 'Déscolarisé', 'Non_scolarise' => 'Non scolarisé'];

    /** Situations familiales considérées comme facteurs de vulnérabilité (RM-02). */
    public const VULNERABILITES = ['orphelin', 'parents divorcés', 'sous tutorat'];

    protected $fillable = ['nom', 'prenom', 'sexe', 'date_naissance', 'lieu_naissance', 'situation', 'derniere_classe', 'cause_descolarisation', 'region', 'departement', 'commune', 'nom_parent', 'telephone_parent', 'handicap', 'situation_familiale', 'structure_id', 'classe_id'];

    protected $casts = ['date_naissance' => 'date:Y-m-d'];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class)->latest();
    }

    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return AccessScope::pour($user)->filtrerParStructure($query, 'apprenants.structure_id');
    }

    /** Âge en années révolues à la date de référence. */
    public function age(?CarbonInterface $date = null): ?int
    {
        return $this->date_naissance ? (int) $this->date_naissance->diffInYears($date ?? Carbon::today()) : null;
    }

    public function estVulnerable(): bool
    {
        if ($this->handicap === 'oui') {
            return true;
        }
        $situation = mb_strtolower((string) $this->situation_familiale);
        foreach (['orphelin', 'divorc', 'tutorat', 'tutelle'] as $motCle) {
            if (str_contains($situation, $motCle)) {
                return true;
            }
        }

        return false;
    }
}
