<?php

namespace App\Http\Controllers;

use App\Http\Requests\StructureRequest;
use App\Models\Atlas;
use App\Models\LangueNationale;
use App\Models\Operateur;
use App\Models\Structure;
use App\Models\TypeOffre;
use App\Services\StructureCodeService;
use App\Services\StructureStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Gestion des structures : codification, recodification, états, périmètre. */
class StructureController extends Controller
{
    private const MENU = 8;
    private const SOUS_MENU = 15;

    public function __construct(private readonly StructureCodeService $codes)
    {
    }

    public function index(Request $request): Response
    {
        $filtres = $request->only(['recherche', 'etat', 'type_offre_id', 'ief']);
        $query = Structure::visiblePour($request->user())->with(['typeOffre', 'operateur'])->withCount(['classes', 'apprenants']);

        if ($r = trim((string) ($filtres['recherche'] ?? ''))) {
            $query->where(fn ($q) => $q->where('nom_structure', 'like', "%{$r}%")->orWhere('code_structure', 'like', "%{$r}%")->orWhere('commune', 'like', "%{$r}%"));
        }
        foreach (['etat', 'type_offre_id', 'ief'] as $champ) {
            if (! empty($filtres[$champ])) {
                $query->where($champ, $filtres[$champ]);
            }
        }

        $base = Structure::visiblePour($request->user());

        return Inertia::render('Structures/Index', [
            'structures' => $this->page($query->orderBy('nom_structure')->paginate(10)->withQueryString(), fn (Structure $s) => [
                'id' => $s->id, 'code' => $s->code_structure, 'nom' => $s->nom_structure, 'type' => $s->typeOffre?->libelle,
                'commune' => $s->commune, 'ief' => $s->ief, 'ia' => $s->ia, 'etat' => $s->etat,
                'classes' => $s->classes_count, 'apprenants' => $s->apprenants_count,
            ]),
            'compteurs' => [
                'total' => (clone $base)->count(),
                'ouvertes' => (clone $base)->where('etat', 'OUVERT')->count(),
                'attente' => (clone $base)->where('etat', 'EN_ATTENTE')->count(),
                'fermees' => (clone $base)->whereIn('etat', ['FERME', 'GELE'])->count(),
            ],
            'filtres' => $filtres,
            'options' => [
                'etats' => StructureStateMachine::LIBELLES,
                'types' => TypeOffre::where('actif', 1)->orderBy('libelle')->get(['id', 'libelle']),
                'iefs' => (clone $base)->select('ief')->distinct()->orderBy('ief')->pluck('ief'),
            ],
            'droits' => $this->droitsEcran(self::MENU, self::SOUS_MENU),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Structures/Formulaire', [
            'structure' => [
                'nom_structure' => '', 'type_offre_id' => null, 'region' => '', 'departement' => '', 'commune' => '', 'quartier' => '',
                'ia' => $user->ia ?? '', 'ief' => $user->ief ?? '', 'latitude' => null, 'longitude' => null,
                'langue_nationale' => '', 'operateur_id' => null, 'etat' => 'EN_ATTENTE',
            ],
            'etatsPossibles' => StructureStateMachine::suivants(null),
        ] + $this->options());
    }

    public function store(StructureRequest $request): RedirectResponse
    {
        $structure = new Structure($request->donnees());
        $structure->etat = 'EN_ATTENTE';
        $this->verifierPerimetreSaisi($structure);

        try {
            $structure->code_structure = $this->codes->genererCode((int) $structure->type_offre_id);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['type_offre_id' => $e->getMessage()]);
        }
        $structure->save();

        return redirect()->route('structures.show', $structure)->with('success', "Structure créée avec le code {$structure->code_structure}.");
    }

    public function show(int $id): Response
    {
        $structure = Structure::withTrashed()->with(['typeOffre', 'operateur', 'ancienne', 'classes.facilitateur'])->withCount('apprenants')->find($id);
        abort_if($structure === null, 404);
        $this->exigerPerimetre($structure, 'structures', $id);

        return Inertia::render('Structures/Fiche', [
            'structure' => [
                'id' => $structure->id, 'code' => $structure->code_structure, 'nom' => $structure->nom_structure,
                'type' => $structure->typeOffre?->libelle, 'region' => $structure->region, 'departement' => $structure->departement,
                'commune' => $structure->commune, 'quartier' => $structure->quartier, 'ia' => $structure->ia, 'ief' => $structure->ief,
                'latitude' => $structure->latitude, 'longitude' => $structure->longitude, 'langue' => $structure->langue_nationale,
                'operateur' => $structure->operateur?->nom_organisation, 'etat' => $structure->etat,
                'etatLibelle' => StructureStateMachine::LIBELLES[$structure->etat] ?? $structure->etat,
                'archivee' => $structure->trashed(), 'apprenants' => $structure->apprenants_count,
                'ancienne' => $structure->ancienne ? ['id' => $structure->ancienne->id, 'code' => $structure->ancienne->code_structure] : null,
                'remplacee_par' => Structure::where('ancienne_structure_id', $structure->id)->first(['id', 'code_structure']),
                'classes' => $structure->classes->map(fn ($c) => ['id' => $c->id, 'code' => $c->code_classe, 'nom' => $c->nom_classe, 'niveau' => $c->niveau, 'facilitateur' => $c->facilitateur ? $c->facilitateur->prenom.' '.$c->facilitateur->nom : null]),
            ],
            'droits' => $this->droitsEcran(self::MENU, self::SOUS_MENU),
        ]);
    }

    public function edit(int $id): Response
    {
        $structure = Structure::find($id);
        abort_if($structure === null, 404);
        $this->exigerPerimetre($structure, 'structures', $id);

        return Inertia::render('Structures/Formulaire', [
            'structure' => ['id' => $structure->id, 'code' => $structure->code_structure] + $structure->only($structure->getFillable()),
            'etatsPossibles' => StructureStateMachine::suivants($structure->etat),
        ] + $this->options());
    }

    public function update(StructureRequest $request, int $id): RedirectResponse
    {
        $structure = Structure::find($id);
        abort_if($structure === null, 404);
        $this->exigerPerimetre($structure, 'structures', $id);
        $donnees = $request->donnees();

        if (! StructureStateMachine::autorise($structure->etat, $donnees['etat'])) {
            throw ValidationException::withMessages(['etat' => 'Ce changement d’état n’est pas autorisé depuis « '.StructureStateMachine::LIBELLES[$structure->etat].' ».']);
        }
        $this->verifierPerimetreSaisi(new Structure($donnees));

        if (StructureCodeService::exigeRecodification($structure, $donnees)) {
            $nouvelle = $this->codes->recodifier($structure, $donnees);

            return redirect()->route('structures.show', $nouvelle)->with('warning', "Recodification effectuée : l'ancien code {$structure->code_structure} est archivé, le nouveau code est {$nouvelle->code_structure}.");
        }

        $structure->update($donnees);

        return redirect()->route('structures.show', $structure)->with('success', 'Structure mise à jour.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $structure = Structure::find($id);
        abort_if($structure === null, 404);
        $this->exigerPerimetre($structure, 'structures', $id);
        $structure->delete(); // suppression logique : le code reste consultable

        return redirect()->route('structures.index')->with('success', 'Structure archivée.');
    }

    /** Un agent ne peut pas créer ou déplacer une structure hors de son périmètre. */
    private function verifierPerimetreSaisi(Structure $structure): void
    {
        if (! $this->scope()->autoriseStructure($structure)) {
            throw ValidationException::withMessages(['ief' => 'L’IA et l’IEF doivent correspondre à votre périmètre.']);
        }
    }

    private function options(): array
    {
        return [
            'options' => [
                'types' => TypeOffre::where('actif', 1)->orderBy('libelle')->get(['id', 'libelle', 'code', 'prefixe'])->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->libelle, 'suffixe' => $t->suffixe()]),
                'langues' => LangueNationale::where('actif', 1)->orderBy('nom')->pluck('nom'),
                'operateurs' => Operateur::orderBy('nom_organisation')->get(['id', 'nom_organisation']),
                'etats' => StructureStateMachine::LIBELLES,
                'atlas' => Atlas::whereIn('id_hierarchie', [Atlas::REGION, Atlas::IA, Atlas::IEF, Atlas::ARRONDISSEMENT, Atlas::COMMUNE])
                    ->orderBy('libelle_structure')->get(['id_atlas', 'libelle_structure', 'id_parent_atlas', 'id_hierarchie'])
                    ->map(fn ($a) => ['id' => $a->id_atlas, 'nom' => $a->libelle_structure, 'parent' => $a->id_parent_atlas, 'niveau' => $a->id_hierarchie]),
            ],
            'perimetre' => ['niveau' => request()->user()->niveau_acces, 'ia' => request()->user()->ia, 'ief' => request()->user()->ief],
        ];
    }
}
