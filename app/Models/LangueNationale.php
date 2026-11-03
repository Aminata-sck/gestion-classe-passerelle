<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LangueNationale extends Model
{
    use Auditable;

    protected $table = 'langues_nationales';

    protected $fillable = ['nom', 'code', 'actif'];
}
