<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Facilitateur;
use App\Models\LangueNationale;
use App\Models\Structure;
use App\Services\StructureCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Gestion des classes. Le code est généré à la création et ne change jamais (RM-05). */
class ClasseController extends Controller
{
    private const MENU = 7;
    private const SOUS_MENU = 14;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Classe::visiblePour($user)->with(['structure', 'facilitateur'])->withCount('apprenants');
        if ($r = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q->where('nom_classe', 'like', "%{$r}%")->orWhere('code_classe', 'like', "%{$r}%"));
        }
        if ($n = $request->input('niveau')) {
            $query->where('niveau', $n);
        }

        return Inertia::render('Classes/Index', [
            'classes' => $this->page($query->orderBy('code_classe')->paginate(10)->withQueryString(), fn (Classe $c) => [
                'id' => $c->id, 'code' => $c->code_classe, 'nom' => $c->nom_classe, 'niveau' => $c->niveau, 'langue' => $c->langue,
                'date_ouverture' => $c->date_ouverture?->format('Y-m-d'), 'structure_id' => $c->structure_id,
                'structure' => $c->structure?->nom_structure, 'facilitateur_id' => $c->facilitateur_id,
                'facilitateur' => $c->facilitateur ? $c->facilitateur->prenom.' '.$c->facilitateur->nom : null,
                'apprenants' => $c->apprenants_count,
            ]),
            'filtres' => $request->only(['recherche', 'niveau']),
            'options' => [
                'niveaux' => Classe::NIVEAUX,
                'structures' => Structure::visiblePour($user)->orderBy('nom_structure')->get(['id', 'nom_structure', 'code_structure']),
                'facilitateurs' => Facilitateur::visiblePour($user)->orderBy('nom')->get(['id', 'prenom', 'nom', 'structure_id']),
                'langues' => LangueNationale::where('actif', 1)->orderBy('nom')->pluck('nom'),
            ],
            'droits' => $this->droitsEcran(self::MENU, self::SOUS_MENU),
        ]);
    }

    public function store(Request $request, StructureCodeService $codes): RedirectResponse
    {
        $data = $this->valider($request);
        $structure = Structure::find($data['structure_id']);
        $this->exigerPerimetre($structure, 'structures', $data['structure_id']);

        $classe = new Classe($data);
        $classe->code_classe = $codes->genererCodeClasse($structure);
        $classe->save();

        return back()->with('success', "Classe créée avec le code {$classe->code_classe}.");
    }

    public function update(Request $request, Classe $classe): RedirectResponse
    {
        $this->exigerPerimetre($classe->structure, 'classes', $classe->id);
        $data = $this->valider($request);
        if ((int) $data['structure_id'] !== (int) $classe->structure_id) {
            return back()->with('error', 'Une classe ne peut pas changer de structure : son code en dépend.');
        }
        $classe->update($data); // le code n'est pas modifiable

        return back()->with('success', 'Classe mise à jour.');
    }

    public function destroy(Classe $classe): RedirectResponse
    {
        $this->exigerPerimetre($classe->structure, 'classes', $classe->id);
        if ($classe->apprenants()->exists()) {
            return back()->with('error', 'Cette classe accueille des apprenants : retirez-les avant de la supprimer.');
        }
        $classe->delete();

        return back()->with('success', 'Classe supprimée.');
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'nom_classe' => ['required', 'string', 'max:100'],
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->whereNull('deleted_at')],
            'facilitateur_id' => ['nullable', 'integer', Rule::exists('facilitateur', 'id')],
            'niveau' => ['required', Rule::in(Classe::NIVEAUX)],
            'langue' => ['nullable', 'string', 'max:50'],
            'date_ouverture' => ['nullable', 'date'],
        ], [], ['nom_classe' => 'nom de la classe', 'structure_id' => 'structure', 'facilitateur_id' => 'facilitateur', 'date_ouverture' => "date d'ouverture"]);
    }
}
