<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use Auditable;

    protected $table = 'programmes';

    protected $fillable = ['nom', 'description', 'actif'];
}
