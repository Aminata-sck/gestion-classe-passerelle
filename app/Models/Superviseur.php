<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Superviseur extends Model
{
    use Auditable;

    protected $table = 'superviseur';

    protected $fillable = ['matricule', 'prenom', 'nom', 'sexe', 'telephone', 'email', 'fonction', 'structure_affectation', 'region', 'departement', 'date_affectation', 'statut'];
}
