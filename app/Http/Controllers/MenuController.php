<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\RolePermission;
use App\Models\SousMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Gestion des menus et sous-menus de la barre latérale. */
class MenuController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Menus/Index', [
            'menus' => Menu::with('sousMenus')->orderBy('ordre')->get()->map(fn (Menu $m) => [
                'id' => $m->id, 'nom_menu' => $m->nom_menu, 'icon' => $m->icon, 'url' => $m->url, 'ordre' => $m->ordre, 'statut' => (bool) $m->statut,
                'sousMenus' => $m->sousMenus->map(fn ($s) => ['id' => $s->id, 'menu_id' => $s->menu_id, 'nom_sous_menu' => $s->nom_sous_menu, 'url' => $s->url, 'icon' => $s->icon, 'ordre' => $s->ordre, 'statut' => (bool) $s->statut]),
            ]),
            'droits' => $this->droitsEcran(6, 17),
        ]);
    }

    public function storeMenu(Request $request): RedirectResponse
    {
        Menu::create($this->validerMenu($request) + ['visible' => true]);

        return back()->with('success', 'Menu créé.');
    }

    public function updateMenu(Request $request, Menu $menu): RedirectResponse
    {
        $menu->update($this->validerMenu($request));

        return back()->with('success', 'Menu mis à jour.');
    }

    public function destroyMenu(Menu $menu): RedirectResponse
    {
        if ($menu->sousMenus()->exists()) {
            return back()->with('error', 'Supprimez d’abord les sous-menus de ce menu.');
        }
        $menu->delete();

        return back()->with('success', 'Menu supprimé.');
    }

    public function storeSousMenu(Request $request): RedirectResponse
    {
        SousMenu::create($this->validerSousMenu($request));

        return back()->with('success', 'Sous-menu créé.');
    }

    public function updateSousMenu(Request $request, SousMenu $sousMenu): RedirectResponse
    {
        $sousMenu->update($this->validerSousMenu($request));

        return back()->with('success', 'Sous-menu mis à jour.');
    }

    public function destroySousMenu(SousMenu $sousMenu): RedirectResponse
    {
        RolePermission::where('sous_menu_id', $sousMenu->id)->delete();
        $sousMenu->delete();

        return back()->with('success', 'Sous-menu supprimé.');
    }

    private function validerMenu(Request $request): array
    {
        return $request->validate([
            'nom_menu' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255', 'regex:/^\/?[a-z0-9\/\-_]*$/i'],
            'ordre' => ['required', 'integer', 'min:0'],
            'statut' => ['boolean'],
        ], ['url.regex' => 'L’URL ne doit contenir que des lettres, des chiffres, « / », « - » et « _ ».'], ['nom_menu' => 'nom du menu']);
    }

    private function validerSousMenu(Request $request): array
    {
        return $request->validate([
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'nom_sous_menu' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:150', 'regex:/^\/?[a-z0-9\/\-_]*$/i'],
            'icon' => ['nullable', 'string', 'max:50'],
            'ordre' => ['required', 'integer', 'min:0'],
            'statut' => ['boolean'],
        ], ['url.regex' => 'L’URL ne doit contenir que des lettres, des chiffres, « / », « - » et « _ ».'], ['nom_sous_menu' => 'nom du sous-menu', 'menu_id' => 'menu']);
    }
}
