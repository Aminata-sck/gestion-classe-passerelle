<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Écrit l'historique des actions (qui, quoi, quand, depuis où, sur quelle donnée).
 * Les champs sensibles ne sont jamais enregistrés en clair.
 */
class AuditLogger
{
    public const MASQUE = '********';

    /** @var list<string> */
    private const SENSIBLES = ['password', 'remember_token', 'token', '_token', 'password_confirmation', 'current_password'];

    public function __construct(private readonly ?Request $request = null)
    {
    }

    public function log(string $action, ?string $entity = null, int|string|null $entityId = null, ?array $old = null, ?array $new = null, ?string $email = null): AuditLog
    {
        $user = Auth::user();
        $request = $this->request ?? request();

        return AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'user_email' => $email ?? $user?->email,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'old_values' => $old ? self::masquer($old) : null,
            'new_values' => $new ? self::masquer($new) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
    }

    /** Remplace la valeur des champs sensibles par un masque. */
    public static function masquer(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSIBLES, true)) {
                $values[$key] = self::MASQUE;
            }
        }

        return $values;
    }
}
