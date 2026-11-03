<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filtre d'interception : vérifie le droit du profil avant le contrôleur.
 * Usage dans les routes : ->middleware('permission:8,15,3').
 */
class CheckPermission
{
    public function __construct(private readonly PermissionService $permissions, private readonly AuditLogger $audit)
    {
    }

    public function handle(Request $request, Closure $next, string $menu, string $sousMenu, string $action): Response
    {
        $user = $request->user();
        if (! $user || ! $this->permissions->peut($user, (int) $menu, (int) $sousMenu, (int) $action)) {
            $this->audit->log('ACCES_REFUSE', 'route', null, null, ['url' => $request->path(), 'droit' => "{$menu}:{$sousMenu}:{$action}"]);
            abort(403, "Vous n'avez pas le droit d'effectuer cette action.");
        }

        return $next($request);
    }
}
