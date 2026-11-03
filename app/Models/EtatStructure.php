<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class EtatStructure extends Model
{
    use Auditable;

    protected $table = 'etat_structures';

    protected $fillable = ['libelle', 'couleur', 'description', 'actif'];
}
