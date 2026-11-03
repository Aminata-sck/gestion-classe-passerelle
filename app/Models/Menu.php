<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use Auditable;

    protected $table = 'menus';

    public $timestamps = false;

    protected $fillable = ['nom_menu', 'icon', 'ordre', 'statut', 'visible', 'url'];

    public function sousMenus(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SousMenu::class)->orderBy('ordre');
    }
}
