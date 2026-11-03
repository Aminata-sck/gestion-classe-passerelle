<?php

namespace App\Http\Controllers;

use App\Models\EtatStructure;
use App\Models\LangueNationale;
use App\Models\Nomenclature;
use App\Models\Programme;
use App\Models\RegleCodification;
use App\Models\SiteStructure;
use App\Models\SourceFinancement;
use App\Models\StatutOccupation;
use App\Models\TypeOffre;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Écran générique pour les neuf référentiels paramétrables. Chaque référentiel
 * décrit son modèle, ses champs et le sous-menu qui porte ses droits.
 */
class ReferentielController extends Controller
{
    public const MENU = 12;

    public const REFERENTIELS = [
        'types-offre' => ['titre' => "Types d'offre", 'modele' => TypeOffre::class, 'sous_menu' => 28, 'champs' => [
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:100'],
            'code' => ['label' => 'Code (le dernier chiffre est le suffixe des codes de structure)', 'regle' => 'required|string|max:50|regex:/^\d+$/'],
            'prefixe' => ['label' => 'Préfixe des codes de classe', 'regle' => 'required|string|max:50'],
            'description' => ['label' => 'Description', 'regle' => 'nullable|string|max:1000', 'type' => 'textarea'],
        ]],
        'langues' => ['titre' => 'Langues nationales', 'modele' => LangueNationale::class, 'sous_menu' => 29, 'champs' => [
            'nom' => ['label' => 'Nom', 'regle' => 'required|string|max:100'],
            'code' => ['label' => 'Code', 'regle' => 'nullable|string|max:20'],
        ]],
        'sites' => ['titre' => 'Sites abritants', 'modele' => SiteStructure::class, 'sous_menu' => 30, 'champs' => [
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:100'],
            'description' => ['label' => 'Description', 'regle' => 'nullable|string|max:1000', 'type' => 'textarea'],
        ]],
        'statuts' => ['titre' => "Statuts d'occupation", 'modele' => StatutOccupation::class, 'sous_menu' => 31, 'champs' => [
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:100'],
            'description' => ['label' => 'Description', 'regle' => 'nullable|string|max:1000', 'type' => 'textarea'],
        ]],
        'financements' => ['titre' => 'Sources de financement', 'modele' => SourceFinancement::class, 'sous_menu' => 32, 'champs' => [
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:150'],
            'description' => ['label' => 'Description', 'regle' => 'nullable|string|max:1000', 'type' => 'textarea'],
        ]],
        'programmes' => ['titre' => 'Programmes', 'modele' => Programme::class, 'sous_menu' => 33, 'champs' => [
            'nom' => ['label' => 'Nom', 'regle' => 'required|string|max:150'],
            'description' => ['label' => 'Description', 'regle' => 'nullable|string|max:1000', 'type' => 'textarea'],
        ]],
        'etats' => ['titre' => 'États des structures', 'modele' => EtatStructure::class, 'sous_menu' => 34, 'champs' => [
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:100'],
            'couleur' => ['label' => 'Couleur', 'regle' => 'nullable|string|max:20'],
            'description' => ['label' => 'Description', 'regle' => 'nullable|string|max:1000', 'type' => 'textarea'],
        ]],
        'nomenclature' => ['titre' => 'Nomenclature', 'modele' => Nomenclature::class, 'sous_menu' => 35, 'champs' => [
            'code' => ['label' => 'Code', 'regle' => 'required|string|max:50'],
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:150'],
            'type_offre_id' => ['label' => "Type d'offre", 'regle' => 'nullable|integer|exists:types_offre,id', 'type' => 'type_offre'],
        ]],
        'codification' => ['titre' => 'Règles de codification', 'modele' => RegleCodification::class, 'sous_menu' => 36, 'champs' => [
            'code' => ['label' => 'Code', 'regle' => 'required|string|max:50'],
            'libelle' => ['label' => 'Libellé', 'regle' => 'required|string|max:150'],
            'format' => ['label' => 'Format', 'regle' => 'nullable|string|max:100'],
            'type_offre_id' => ['label' => "Type d'offre", 'regle' => 'nullable|integer|exists:types_offre,id', 'type' => 'type_offre'],
        ]],
    ];

    public function index(string $referentiel): Response
    {
        $def = self::definition($referentiel);
        $modele = $def['modele'];
        $ordre = array_key_first($def['champs']);

        return Inertia::render('Referentiels/Index', [
            'cle' => $referentiel,
            'titre' => $def['titre'],
            'champs' => collect($def['champs'])->map(fn ($c, $nom) => ['nom' => $nom, 'label' => $c['label'], 'type' => $c['type'] ?? 'text', 'requis' => str_contains($c['regle'], 'required')])->values(),
            'lignes' => $modele::orderBy($ordre)->get()->map(fn (Model $m) => $m->only(['id', 'actif', ...array_keys($def['champs'])])),
            'typesOffre' => TypeOffre::orderBy('libelle')->get(['id', 'libelle']),
            'referentiels' => collect(self::REFERENTIELS)->map(fn ($d, $k) => ['cle' => $k, 'titre' => $d['titre']])->values(),
            'droits' => $this->droitsEcran(self::MENU, $def['sous_menu']),
        ]);
    }

    public function store(Request $request, string $referentiel): RedirectResponse
    {
        $def = self::definition($referentiel);
        $def['modele']::create($this->valider($request, $def));

        return back()->with('success', 'Élément ajouté.');
    }

    public function update(Request $request, string $referentiel, int $id): RedirectResponse
    {
        $def = self::definition($referentiel);
        $def['modele']::findOrFail($id)->update($this->valider($request, $def));

        return back()->with('success', 'Élément mis à jour.');
    }

    public function destroy(string $referentiel, int $id): RedirectResponse
    {
        $def = self::definition($referentiel);
        // Désactivation plutôt que suppression : des structures peuvent y faire référence.
        $def['modele']::findOrFail($id)->update(['actif' => false]);

        return back()->with('success', 'Élément désactivé.');
    }

    public static function definition(string $referentiel): array
    {
        abort_unless(isset(self::REFERENTIELS[$referentiel]), 404);

        return self::REFERENTIELS[$referentiel];
    }

    private function valider(Request $request, array $def): array
    {
        $regles = collect($def['champs'])->map(fn ($c) => $c['regle'])->all() + ['actif' => 'boolean'];
        $labels = collect($def['champs'])->map(fn ($c) => mb_strtolower(strtok($c['label'], '(')))->all();

        return $request->validate($regles, [], $labels);
    }
}
