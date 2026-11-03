<?php

namespace App\Http\Controllers;

use App\Models\Apprenant;
use App\Models\Structure;
use App\Services\SelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/** Classement de sélection d'une structure pour un nombre de places donné. */
class SelectionController extends Controller
{
    public function __invoke(Request $request, SelectionService $selection): Response
    {
        $user = $request->user();
        $data = $request->validate([
            'structure_id' => ['nullable', 'integer'],
            'places' => ['nullable', 'integer', 'min:1', 'max:200'],
            'date' => ['nullable', 'date'],
        ]);
        $places = (int) ($data['places'] ?? 25);
        $date = isset($data['date']) ? Carbon::parse($data['date']) : Carbon::today();

        $resultat = null;
        $structure = null;
        if (! empty($data['structure_id'])) {
            $structure = Structure::find($data['structure_id']);
            abort_if($structure === null, 404);
            $this->exigerPerimetre($structure, 'structures', $data['structure_id']);
            $resultat = $selection->classer(Apprenant::where('structure_id', $structure->id)->get(), $places, $date);
        }

        return Inertia::render('Selection/Index', [
            'structures' => Structure::visiblePour($user)->orderBy('nom_structure')->get(['id', 'nom_structure', 'code_structure']),
            'criteres' => ['structure_id' => $structure?->id, 'places' => $places, 'date' => $date->format('Y-m-d')],
            'resultat' => $resultat,
        ]);
    }
}
