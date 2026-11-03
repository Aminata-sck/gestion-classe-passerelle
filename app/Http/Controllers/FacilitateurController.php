<?php

namespace App\Http\Controllers;

use App\Models\Facilitateur;
use App\Models\Structure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FacilitateurController extends Controller
{
    private const MENU = 2;
    private const SOUS_MENU = 2;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Facilitateur::visiblePour($user)->with('structure');
        if ($r = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q->where('nom', 'like', "%{$r}%")->orWhere('prenom', 'like', "%{$r}%")->orWhere('matricule', 'like', "%{$r}%"));
        }

        return Inertia::render('Facilitateurs/Index', [
            'facilitateurs' => $this->page($query->orderBy('nom')->paginate(10)->withQueryString(), fn (Facilitateur $f) => $f->only([
                'id', 'matricule', 'prenom', 'nom', 'sexe', 'telephone', 'email', 'adresse', 'niveau_etude', 'specialite', 'type_contrat', 'structure_id', 'statut',
            ]) + ['date_naissance' => $f->date_naissance?->format('Y-m-d'), 'date_recrutement' => $f->date_recrutement?->format('Y-m-d'), 'structure' => $f->structure?->nom_structure]),
            'filtres' => $request->only('recherche'),
            'options' => ['structures' => Structure::visiblePour($user)->orderBy('nom_structure')->get(['id', 'nom_structure'])],
            'droits' => $this->droitsEcran(self::MENU, self::SOUS_MENU),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);
        $this->exigerPerimetre(Structure::find($data['structure_id']), 'facilitateur', null);
        Facilitateur::create($data);

        return back()->with('success', 'Facilitateur enregistré.');
    }

    public function update(Request $request, Facilitateur $facilitateur): RedirectResponse
    {
        $this->exigerPerimetre($facilitateur->structure, 'facilitateur', $facilitateur->id);
        $data = $this->valider($request, $facilitateur->id);
        $this->exigerPerimetre(Structure::find($data['structure_id']), 'facilitateur', $facilitateur->id);
        $facilitateur->update($data);

        return back()->with('success', 'Facilitateur mis à jour.');
    }

    public function destroy(Facilitateur $facilitateur): RedirectResponse
    {
        $this->exigerPerimetre($facilitateur->structure, 'facilitateur', $facilitateur->id);
        $facilitateur->delete();

        return back()->with('success', 'Facilitateur supprimé.');
    }

    private function valider(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'matricule' => ['required', 'string', 'max:100', Rule::unique('facilitateur', 'matricule')->ignore($id)],
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'date_naissance' => ['required', 'date', 'before:-16 years'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'niveau_etude' => ['nullable', 'string', 'max:150'],
            'specialite' => ['nullable', 'string', 'max:150'],
            'date_recrutement' => ['required', 'date'],
            'type_contrat' => ['required', 'string', 'max:100'],
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->whereNull('deleted_at')],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ], [], ['date_naissance' => 'date de naissance', 'date_recrutement' => 'date de recrutement', 'type_contrat' => 'type de contrat', 'structure_id' => 'structure']);
    }
}
