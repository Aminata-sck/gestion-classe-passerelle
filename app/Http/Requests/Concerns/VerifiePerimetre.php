<?php

namespace App\Http\Requests\Concerns;

use App\Models\Structure;
use App\Services\AccessScope;
use App\Services\AuditLogger;

/**
 * Contrôle du périmètre AVANT la validation : une donnée hors périmètre
 * reçoit un refus neutre (404), jamais des messages de validation qui
 * confirmeraient son existence.
 */
trait VerifiePerimetre
{
    protected function exigerPerimetre(?Structure $structure, string $entite, int|string|null $id): void
    {
        if (! AccessScope::pour($this->user())->autoriseStructure($structure)) {
            app(AuditLogger::class)->log('ACCES_REFUSE', $entite, $id, null, ['motif' => 'hors périmètre']);
            abort(404);
        }
    }
}
