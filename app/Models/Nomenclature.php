<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Nomenclature extends Model
{
    use Auditable;

    protected $table = 'nomenclatures';

    protected $fillable = ['type_offre_id', 'code', 'libelle', 'nom_site', 'prefixe', 'format', 'description', 'actif'];
}
