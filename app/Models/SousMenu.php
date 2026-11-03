<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SousMenu extends Model
{
    use Auditable;

    protected $table = 'sous_menus';

    public const UPDATED_AT = null;

    protected $fillable = ['menu_id', 'nom_sous_menu', 'url', 'icon', 'ordre', 'permission_id', 'statut'];

    public function menu(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
