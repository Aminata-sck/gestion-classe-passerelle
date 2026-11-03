<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class RegleCodification extends Model
{
    use Auditable;

    protected $table = 'regles_codification';

    protected $fillable = ['type_offre_id', 'numero_ordre', 'code', 'libelle', 'nom_site', 'prefixe', 'format', 'description', 'actif'];
}
