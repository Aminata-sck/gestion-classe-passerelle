<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Historise automatiquement la création, la modification et la suppression
 * d'un modèle (patron observateur). Pour une modification, seules les valeurs
 * réellement changées sont enregistrées, avant et après.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            app(AuditLogger::class)->log('CREATION', $model->getTable(), $model->getKey(), null, $model->attributesToArray());
        });

        static::updated(function (Model $model) {
            $changes = collect($model->getChanges())->except(['updated_at'])->all();
            if ($changes === []) {
                return;
            }
            $before = array_intersect_key($model->getOriginal(), $changes);
            app(AuditLogger::class)->log('MODIFICATION', $model->getTable(), $model->getKey(), $before, $changes);
        });

        static::deleted(function (Model $model) {
            app(AuditLogger::class)->log('SUPPRESSION', $model->getTable(), $model->getKey(), $model->attributesToArray(), null);
        });
    }
}
