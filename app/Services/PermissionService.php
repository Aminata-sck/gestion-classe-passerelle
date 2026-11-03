<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\RolePermission;
use App\Models\User;

/**
 * Droits par profil (RBAC) : un droit est le triplet menu x sous-menu x action
 * (1 = consulter, 2 = ajouter, 3 = modifier, 4 = supprimer).
 */
class PermissionService
{
    public const CONSULTER = 1;
    public const AJOUTER = 2;
    public const MODIFIER = 3;
    public const SUPPRIMER = 4;

    /** @var array<int, array<string, true>> */
    private array $cache = [];

    /** @return array<string, true> clés « menu:sousMenu:action » */
    public function droits(User $user): array
    {
        return $this->cache[$user->id] ??= RolePermission::where('role_id', $user->role_id)
            ->get(['menu_id', 'sous_menu_id', 'permission_id'])
            ->mapWithKeys(fn ($d) => ["{$d->menu_id}:{$d->sous_menu_id}:{$d->permission_id}" => true])
            ->all();
    }

    public function peut(User $user, int $menu, int $sousMenu, int $action): bool
    {
        return isset($this->droits($user)["{$menu}:{$sousMenu}:{$action}"]);
    }

    /** Menu latéral : uniquement les sous-menus que le profil peut consulter. */
    /**
     * Adresses de l'ancienne application (CodeIgniter) encore présentes dans
     * la table des menus d'une base reprise : elles sont traduites vers les
     * adresses de la nouvelle application.
     */
    public const ANCIENNES_URLS = [
        '/audit' => '/journal',
        '/journal-audit' => '/journal',
        '/apprenant/selection' => '/selection',
        '/apprenants/selection' => '/selection',
        '/parametres/configuration-structures' => '/configuration/types-offre',
    ];

    public static function urlActuelle(?string $url): string
    {
        $url = '/'.trim((string) $url, '/ ');

        return self::ANCIENNES_URLS[strtolower($url)] ?? $url;
    }

    public function menu(User $user): array
    {
        $droits = $this->droits($user);

        return Menu::with(['sousMenus' => fn ($q) => $q->where('statut', 1)])
            ->where('statut', 1)->where('visible', 1)->orderBy('ordre')->get()
            ->map(function (Menu $menu) use ($droits) {
                $items = $menu->sousMenus
                    ->filter(fn ($sm) => isset($droits["{$menu->id}:{$sm->id}:".self::CONSULTER]))
                    ->map(fn ($sm) => ['id' => $sm->id, 'label' => $sm->nom_sous_menu, 'url' => self::urlActuelle($sm->url)])
                    ->values();

                return ['id' => $menu->id, 'label' => $menu->nom_menu, 'icon' => $menu->icon, 'items' => $items];
            })
            ->filter(fn ($m) => $m['items']->isNotEmpty())
            ->values()
            ->all();
    }
}
