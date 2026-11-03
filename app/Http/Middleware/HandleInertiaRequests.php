<?php

namespace App\Http\Middleware;

use App\Services\AccessScope;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

/** Données partagées par toutes les pages Vue (utilisateur, menu, droits, messages). */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = app(PermissionService::class);

        return array_merge(parent::share($request), [
            'app' => ['nom' => config('app.name')],
            'auth' => $user ? [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom_complet,
                    'email' => $user->email,
                    'profil' => $user->role?->nom_role,
                    'niveau' => $user->niveau_acces,
                    'perimetre' => $this->libellePerimetre($user),
                ],
                'droits' => array_keys($permissions->droits($user)),
            ] : null,
            'menu' => fn () => $user ? $permissions->menu($user) : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ]);
    }

    private function libellePerimetre($user): string
    {
        $scope = AccessScope::pour($user);

        return match ($scope->niveau) {
            AccessScope::NATIONAL => 'National',
            AccessScope::IA => $user->ia,
            AccessScope::IEF => $user->ief,
            AccessScope::STRUCTURE => $user->structure?->nom_structure ?? 'Structure',
            default => '—',
        };
    }
}
