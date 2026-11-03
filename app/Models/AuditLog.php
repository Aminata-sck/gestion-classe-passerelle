<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Entrée de l'historique des actions. Aucune page ne permet de la modifier. */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'user_email', 'action', 'entity', 'entity_id', 'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at'];

    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
}
