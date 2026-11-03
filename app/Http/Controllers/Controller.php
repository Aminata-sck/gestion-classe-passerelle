<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use App\Services\PermissionService;

/**
 * Contrôleur de base : périmètre de l'utilisateur, droits sur l'écran et
 * refus neutre d'un accès hors périmètre.
 */
abstract class Controller
{
    protected function scope(): AccessScope
    {
        return AccessScope::pour(request()->user());
    }

    /** Droits d'ajout, de modification et de suppression sur un écran, transmis à Vue. */
    protected function droitsEcran(int $menu, int $sousMenu): array
    {
        $p = app(PermissionService::class);
        $u = request()->user();

        return [
            'ajouter' => $p->peut($u, $menu, $sousMenu, PermissionService::AJOUTER),
            'modifier' => $p->peut($u, $menu, $sousMenu, PermissionService::MODIFIER),
            'supprimer' => $p->peut($u, $menu, $sousMenu, PermissionService::SUPPRIMER),
        ];
    }

    /**
     * Refuse l'accès à une donnée hors périmètre avec un message neutre (404),
     * qui ne révèle pas si la donnée existe, et historise le refus.
     */
    protected function exigerPerimetre(?Structure $structure, string $entite, int|string|null $id): void
    {
        if (! $this->scope()->autoriseStructure($structure)) {
            app(AuditLogger::class)->log('ACCES_REFUSE', $entite, $id, null, ['motif' => 'hors périmètre']);
            abort(404);
        }
    }

    /** Pagination au format attendu par le composant Vue. */
    protected function page($paginator, callable $map): array
    {
        return [
            'data' => collect($paginator->items())->map($map)->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'pages' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
