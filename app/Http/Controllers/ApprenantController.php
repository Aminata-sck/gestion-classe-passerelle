<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprenantRequest;
use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Structure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Enrôlement et suivi des apprenants, saisie des évaluations. */
class ApprenantController extends Controller
{
    private const MENU = 5;
    private const SOUS_MENU = 13;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Apprenant::visiblePour($user)->with(['structure', 'classe'])->withCount('evaluations');
        if ($r = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q->where('nom', 'like', "%{$r}%")->orWhere('prenom', 'like', "%{$r}%"));
        }
        foreach (['structure_id', 'sexe', 'situation'] as $champ) {
            if ($v = $request->input($champ)) {
                $query->where($champ, $v);
            }
        }

        return Inertia::render('Apprenants/Index', [
            'apprenants' => $this->page($query->orderBy('nom')->orderBy('prenom')->paginate(12)->withQueryString(), fn (Apprenant $a) => [
                'id' => $a->id, 'nom' => $a->nom, 'prenom' => $a->prenom, 'sexe' => $a->sexe, 'age' => $a->age(),
                'situation' => Apprenant::SITUATIONS[$a->situation] ?? '—', 'vulnerable' => $a->estVulnerable(),
                'structure' => $a->structure?->nom_structure, 'classe' => $a->classe?->code_classe, 'evaluations' => $a->evaluations_count,
            ]),
            'filtres' => $request->only(['recherche', 'structure_id', 'sexe', 'situation']),
            'options' => $this->options($user),
            'droits' => $this->droitsEcran(self::MENU, self::SOUS_MENU),
        ]);
    }

    public function store(ApprenantRequest $request): RedirectResponse
    {
        $data = $this->verifier($request->validated());
        $apprenant = Apprenant::create($data);

        return redirect()->route('apprenants.show', $apprenant)->with('success', 'Apprenant enrôlé.');
    }

    public function show(Request $request, int $id): Response
    {
        $a = Apprenant::with(['structure', 'classe', 'evaluations.auteur'])->find($id);
        abort_if($a === null, 404);
        $this->exigerPerimetre($a->structure, 'apprenants', $id);

        return Inertia::render('Apprenants/Fiche', [
            'apprenant' => $a->only($a->getFillable()) + [
                'id' => $a->id, 'date_naissance' => $a->date_naissance?->format('Y-m-d'), 'age' => $a->age(),
                'situation_libelle' => Apprenant::SITUATIONS[$a->situation] ?? '—', 'vulnerable' => $a->estVulnerable(),
                'structure' => $a->structure?->nom_structure, 'classe' => $a->classe ? $a->classe->code_classe.' — '.$a->classe->nom_classe : null,
            ],
            'evaluations' => $a->evaluations->map(fn (Evaluation $e) => [
                'id' => $e->id, 'libelle' => $e->libelle, 'note1' => $e->note1, 'note2' => $e->note2, 'composition' => $e->composition,
                'moyenne' => $e->moyenne, 'date' => $e->created_at?->format('d/m/Y'), 'auteur' => $e->auteur?->nom_complet,
            ]),
            'options' => $this->options($request->user()),
            'droits' => $this->droitsEcran(self::MENU, self::SOUS_MENU),
        ]);
    }

    public function update(ApprenantRequest $request, Apprenant $apprenant): RedirectResponse
    {
        $this->exigerPerimetre($apprenant->structure, 'apprenants', $apprenant->id);
        $apprenant->update($this->verifier($request->validated()));

        return back()->with('success', 'Dossier mis à jour.');
    }

    public function destroy(Apprenant $apprenant): RedirectResponse
    {
        $this->exigerPerimetre($apprenant->structure, 'apprenants', $apprenant->id);
        $apprenant->evaluations()->delete();
        $apprenant->delete();

        return redirect()->route('apprenants.index')->with('success', 'Apprenant supprimé.');
    }

    /** La structure doit être dans le périmètre et la classe dans cette structure. */
    private function verifier(array $data): array
    {
        $this->exigerPerimetre(Structure::find($data['structure_id']), 'structures', $data['structure_id']);
        if (! empty($data['classe_id']) && (int) Classe::find($data['classe_id'])?->structure_id !== (int) $data['structure_id']) {
            throw ValidationException::withMessages(['classe_id' => 'La classe doit appartenir à la structure choisie.']);
        }

        return $data;
    }

    private function options($user): array
    {
        return [
            'structures' => Structure::visiblePour($user)->orderBy('nom_structure')->get(['id', 'nom_structure']),
            'classes' => Classe::visiblePour($user)->orderBy('code_classe')->get(['id', 'code_classe', 'nom_classe', 'structure_id']),
            'situations' => Apprenant::SITUATIONS,
            'situationsFamiliales' => ['Vit avec ses deux parents', 'Orphelin', 'Parents divorcés', 'Sous tutorat', 'Autre'],
        ];
    }
}
