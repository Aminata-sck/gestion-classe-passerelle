<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Operateur extends Model
{
    use Auditable;

    protected $table = 'operateur';

    protected $fillable = ['code_operateur', 'nom_organisation', 'type_operateur', 'prenom_responsable', 'nom_responsable', 'telephone', 'email', 'adresse', 'numero_agrement', 'date_debut', 'date_fin', 'structure_id', 'statut'];
}
