<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Profils et matrice des droits (menu x sous-menu x action). */
class ProfilController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Profils/Index', [
            'profils' => Role::withCount('users')->orderBy('nom_role')->get()->map(fn (Role $r) => [
                'id' => $r->id, 'nom' => $r->nom_role, 'utilisateurs' => $r->users_count,
                'droits' => RolePermission::where('role_id', $r->id)->get()->map(fn ($d) => "{$d->menu_id}:{$d->sous_menu_id}:{$d->permission_id}"),
            ]),
            'menus' => Menu::with('sousMenus')->orderBy('ordre')->get()->map(fn (Menu $m) => [
                'id' => $m->id, 'nom' => $m->nom_menu,
                'sousMenus' => $m->sousMenus->map(fn ($s) => ['id' => $s->id, 'nom' => $s->nom_sous_menu]),
            ]),
            'actions' => Permission::orderBy('id')->get(['id', 'nom_permission']),
            'droits' => $this->droitsEcran(6, 12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);
        DB::transaction(function () use ($data) {
            $role = Role::create(['nom_role' => $data['nom']]);
            $this->enregistrerDroits($role, $data['droits'] ?? []);
        });

        return back()->with('success', 'Profil créé.');
    }

    public function update(Request $request, Role $profil): RedirectResponse
    {
        $data = $this->valider($request, $profil->id);
        DB::transaction(function () use ($profil, $data) {
            $profil->update(['nom_role' => $data['nom']]);
            $this->enregistrerDroits($profil, $data['droits'] ?? []);
        });

        return back()->with('success', 'Profil mis à jour.');
    }

    public function destroy(Role $profil): RedirectResponse
    {
        if ($profil->users()->exists()) {
            return back()->with('error', 'Ce profil est attribué à des utilisateurs : il ne peut pas être supprimé.');
        }
        DB::transaction(function () use ($profil) {
            RolePermission::where('role_id', $profil->id)->delete();
            $profil->delete();
        });

        return back()->with('success', 'Profil supprimé.');
    }

    private function valider(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:100', Rule::unique('roles', 'nom_role')->ignore($id)],
            'droits' => ['array'],
            'droits.*' => ['string', 'regex:/^\d+:\d+:[1-4]$/'],
        ], [], ['nom' => 'nom du profil']);
    }

    private function enregistrerDroits(Role $role, array $droits): void
    {
        $avant = RolePermission::where('role_id', $role->id)->get()->map(fn ($d) => "{$d->menu_id}:{$d->sous_menu_id}:{$d->permission_id}")->sort()->values()->all();
        RolePermission::where('role_id', $role->id)->delete();
        foreach (array_unique($droits) as $droit) {
            [$menu, $sousMenu, $action] = array_map('intval', explode(':', $droit));
            RolePermission::create(['role_id' => $role->id, 'menu_id' => $menu, 'sous_menu_id' => $sousMenu, 'permission_id' => $action]);
        }
        $apres = collect($droits)->unique()->sort()->values()->all();
        if ($avant !== $apres) {
            app(AuditLogger::class)->log('DROITS_MODIFIES', 'roles', $role->id, ['droits' => $avant], ['droits' => $apres]);
        }
    }
}
