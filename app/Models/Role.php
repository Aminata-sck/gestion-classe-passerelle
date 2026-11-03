<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Auditable;

    protected $table = 'roles';

    protected $fillable = ['nom_role'];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function droits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RolePermission::class);
    }
}
