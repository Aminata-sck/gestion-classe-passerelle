<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SiteStructure extends Model
{
    use Auditable;

    protected $table = 'sites_structure';

    protected $fillable = ['libelle', 'description', 'actif'];
}
