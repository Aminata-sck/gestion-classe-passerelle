<?php

namespace App\Http\Controllers;

use App\Models\Operateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Opérateurs (organisations qui gèrent des structures) : référentiel national. */
class OperateurController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Operateur::query();
        if ($r = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q->where('nom_organisation', 'like', "%{$r}%")->orWhere('code_operateur', 'like', "%{$r}%"));
        }

        return Inertia::render('Operateurs/Index', [
            'operateurs' => $this->page($query->orderBy('nom_organisation')->paginate(10)->withQueryString(), fn (Operateur $o) => $o->only($o->getFillable()) + ['id' => $o->id]),
            'filtres' => $request->only('recherche'),
            'droits' => $this->droitsEcran(4, 4),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Operateur::create($this->valider($request));

        return back()->with('success', 'Opérateur enregistré.');
    }

    public function update(Request $request, Operateur $operateur): RedirectResponse
    {
        $operateur->update($this->valider($request, $operateur->id));

        return back()->with('success', 'Opérateur mis à jour.');
    }

    public function destroy(Operateur $operateur): RedirectResponse
    {
        if (\App\Models\Structure::where('operateur_id', $operateur->id)->exists()) {
            return back()->with('error', 'Cet opérateur gère des structures : il ne peut pas être supprimé.');
        }
        $operateur->delete();

        return back()->with('success', 'Opérateur supprimé.');
    }

    private function valider(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code_operateur' => ['required', 'string', 'max:100', Rule::unique('operateur', 'code_operateur')->ignore($id)],
            'nom_organisation' => ['required', 'string', 'max:150'],
            'type_operateur' => ['required', 'string', 'max:100'],
            'prenom_responsable' => ['required', 'string', 'max:100'],
            'nom_responsable' => ['required', 'string', 'max:100'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'numero_agrement' => ['nullable', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ], [], ['code_operateur' => 'code', 'nom_organisation' => 'organisation', 'type_operateur' => 'type', 'date_debut' => 'date de début', 'date_fin' => 'date de fin']);
    }
}
