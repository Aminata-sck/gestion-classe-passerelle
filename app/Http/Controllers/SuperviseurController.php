<?php

namespace App\Http\Controllers;

use App\Models\Superviseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SuperviseurController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Superviseur::query();
        if ($r = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q->where('nom', 'like', "%{$r}%")->orWhere('prenom', 'like', "%{$r}%")->orWhere('matricule', 'like', "%{$r}%"));
        }

        return Inertia::render('Superviseurs/Index', [
            'superviseurs' => $this->page($query->orderBy('nom')->paginate(10)->withQueryString(), fn (Superviseur $s) => $s->only($s->getFillable()) + ['id' => $s->id]),
            'filtres' => $request->only('recherche'),
            'droits' => $this->droitsEcran(3, 3),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Superviseur::create($this->valider($request));

        return back()->with('success', 'Superviseur enregistré.');
    }

    public function update(Request $request, Superviseur $superviseur): RedirectResponse
    {
        $superviseur->update($this->valider($request, $superviseur->id));

        return back()->with('success', 'Superviseur mis à jour.');
    }

    public function destroy(Superviseur $superviseur): RedirectResponse
    {
        $superviseur->delete();

        return back()->with('success', 'Superviseur supprimé.');
    }

    private function valider(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'matricule' => ['required', 'string', 'max:50', Rule::unique('superviseur', 'matricule')->ignore($id)],
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'fonction' => ['required', Rule::in(['IEF', 'IA', 'Central'])],
            'structure_affectation' => ['nullable', 'string', 'max:150'],
            'region' => ['nullable', 'string', 'max:100'],
            'departement' => ['nullable', 'string', 'max:100'],
            'date_affectation' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ], [], ['structure_affectation' => "structure d'affectation", 'date_affectation' => "date d'affectation"]);
    }
}
