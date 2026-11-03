<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Résultat d'un apprenant : deux devoirs et une composition, notés sur 20. */
class Evaluation extends Model
{
    use Auditable;

    protected $fillable = ['apprenant_id', 'classe_id', 'libelle', 'note1', 'note2', 'composition', 'moyenne', 'saisi_par'];

    protected $casts = ['note1' => 'float', 'note2' => 'float', 'composition' => 'float', 'moyenne' => 'float'];

    /** RM-06 : (moyenne des devoirs + composition) / 2, arrondie au centième. */
    public static function calculerMoyenne(float $note1, float $note2, float $composition): float
    {
        return round((($note1 + $note2) / 2 + $composition) / 2, 2);
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par');
    }
}
