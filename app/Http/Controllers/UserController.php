<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Atlas;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Gestion des comptes : profil, périmètre, invitation, blocage. */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with(['role', 'structure']);
        if ($r = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q->where('nom', 'like', "%{$r}%")->orWhere('prenom', 'like', "%{$r}%")->orWhere('email', 'like', "%{$r}%"));
        }

        return Inertia::render('Utilisateurs/Index', [
            'utilisateurs' => $this->page($query->orderBy('nom')->paginate(10)->withQueryString(), fn (User $u) => [
                'id' => $u->id, 'nom' => $u->nom, 'prenom' => $u->prenom, 'ine' => $u->ine, 'email' => $u->email,
                'role_id' => $u->role_id, 'profil' => $u->role?->nom_role, 'status' => $u->status,
                'niveau_acces' => $u->niveau_acces, 'ia' => $u->ia, 'ief' => $u->ief, 'structure_id' => $u->structure_id,
                'perimetre' => match ($u->niveau_acces) {
                    'NATIONAL' => 'National', 'IA' => $u->ia, 'IEF' => $u->ief, 'STRUCTURE' => $u->structure?->nom_structure, default => '—',
                },
                'doit_changer' => $u->must_change_password,
            ]),
            'filtres' => $request->only('recherche'),
            'options' => [
                'roles' => Role::orderBy('nom_role')->get(['id', 'nom_role']),
                'niveaux' => User::NIVEAUX,
                'ias' => Atlas::where('id_hierarchie', Atlas::IA)->orderBy('libelle_structure')->get(['id_atlas', 'libelle_structure']),
                'iefs' => Atlas::where('id_hierarchie', Atlas::IEF)->orderBy('libelle_structure')->get(['id_atlas', 'libelle_structure', 'id_parent_atlas']),
                'structures' => Structure::orderBy('nom_structure')->get(['id', 'nom_structure']),
            ],
            'droits' => $this->droitsEcran(6, 11),
            'moi' => $request->user()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);
        $motDePasse = $this->motDePasseTemporaire();
        $user = User::create($data + ['password' => $motDePasse, 'must_change_password' => true, 'status' => true]);

        try {
            Mail::to($user->email)->send(new InvitationMail($user, $motDePasse));
            $message = ['success' => "Compte créé. L'invitation a été envoyée à {$user->email}."];
        } catch (\Throwable $e) {
            report($e);
            // Le mot de passe temporaire est affiché une seule fois à l'administrateur.
            $message = ['warning' => "Compte créé, mais l'e-mail n'a pas pu être envoyé. Mot de passe temporaire à transmettre : {$motDePasse}"];
        }

        return back()->with($message);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $user->update($this->valider($request, $user->id));

        return back()->with('success', 'Compte mis à jour.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas bloquer votre propre compte.');
        }
        $user->update(['status' => ! $user->status]);

        return back()->with('success', $user->status ? 'Compte débloqué.' : 'Compte bloqué.');
    }

    public function reset(User $user): RedirectResponse
    {
        $motDePasse = $this->motDePasseTemporaire();
        $user->forceFill(['password' => $motDePasse, 'must_change_password' => true])->save();
        app(AuditLogger::class)->log('MOT_DE_PASSE_REINITIALISE', 'users', $user->id);

        return back()->with('warning', "Nouveau mot de passe temporaire pour {$user->email} : {$motDePasse}");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        $user->delete();

        return back()->with('success', 'Compte supprimé.');
    }

    /** Recherche d'un agent par son IEN ou son e-mail : annuaire local, puis API du personnel. */
    public function rechercherPersonnel(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if ($q === '') {
            return response()->json(['message' => 'Saisissez un IEN ou un e-mail professionnel.'], 422);
        }
        $local = Personnel::where('id_atlas', $q)->orWhere('addMail', $q)->first();
        if ($local) {
            return response()->json(['source' => 'local', 'nom' => $local->nom, 'prenom' => $local->prenom, 'ine' => $local->id_atlas, 'email' => $local->addMail]);
        }
        $url = config('services.personnel.url');
        if (! $url) {
            return response()->json(['message' => 'Agent introuvable dans l’annuaire local.'], 404);
        }
        try {
            $r = Http::timeout(10)->get($url, ['ien' => Str::upper($q)]);
            if (! $r->successful() || ! $r->json('nom')) {
                return response()->json(['message' => 'Agent introuvable.'], 404);
            }

            return response()->json(['source' => 'api'] + $r->json());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Le service du personnel ne répond pas.'], 503);
        }
    }

    private function valider(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'ine' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'niveau_acces' => ['required', Rule::in(User::NIVEAUX)],
            'ia' => ['nullable', 'string', 'max:255'],
            'ief' => ['nullable', 'string', 'max:255'],
            'structure_id' => ['nullable', 'integer', Rule::exists('structures', 'id')],
        ], [], ['role_id' => 'profil', 'niveau_acces' => "niveau d'accès", 'structure_id' => 'structure']);
        $data['email'] = Str::lower($data['email']);

        // Seuls les champs utiles au niveau choisi sont conservés.
        $data['ia'] = in_array($data['niveau_acces'], ['IA', 'IEF'], true) ? ($data['ia'] ?? null) : null;
        $data['ief'] = $data['niveau_acces'] === 'IEF' ? ($data['ief'] ?? null) : null;
        $data['structure_id'] = $data['niveau_acces'] === 'STRUCTURE' ? ($data['structure_id'] ?? null) : null;

        $scope = new AccessScope($data['niveau_acces'], AccessScope::normaliser($data['ia']), AccessScope::normaliser($data['ief']), $data['structure_id']);
        if (! $scope->estComplet()) {
            throw ValidationException::withMessages(['niveau_acces' => 'Le rattachement est incomplet pour ce niveau d’accès.']);
        }

        return $data;
    }

    private function motDePasseTemporaire(): string
    {
        return Str::password(14, symbols: false);
    }
}
