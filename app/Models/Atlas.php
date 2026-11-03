<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atlas extends Model
{
    protected $table = 'atlas';

    protected $fillable = ['id_atlas', 'libelle_structure', 'id_parent_atlas', 'id_hierarchie', 'libelle_hierarchie'];

    public const REGION = 8;
    public const IA = 9;
    public const IEF = 11;
    public const ARRONDISSEMENT = 12;
    public const COMMUNE = 13;
}
