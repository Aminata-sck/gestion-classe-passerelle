<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SourceFinancement extends Model
{
    use Auditable;

    protected $table = 'sources_financement';

    protected $fillable = ['libelle', 'description', 'actif'];
}
