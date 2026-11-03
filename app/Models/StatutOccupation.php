<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class StatutOccupation extends Model
{
    use Auditable;

    protected $table = 'statuts_occupation';

    protected $fillable = ['libelle', 'description', 'actif'];
}
