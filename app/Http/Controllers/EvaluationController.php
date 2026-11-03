<?php

namespace App\Http\Controllers;

use App\Models\Apprenant;
use App\Models\Evaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Saisie des évaluations : la moyenne est calculée par le serveur (RM-06). */
class EvaluationController extends Controller
{
    public function store(Request $request, Apprenant $apprenant): RedirectResponse
    {
        $this->exigerPerimetre($apprenant->structure, 'apprenants', $apprenant->id);
        $note = ['required', 'numeric', 'between:0,20'];
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100'],
            'note1' => $note, 'note2' => $note, 'composition' => $note,
        ], [], ['note1' => 'note du devoir 1', 'note2' => 'note du devoir 2', 'composition' => 'note de composition']);

        Evaluation::create($data + [
            'apprenant_id' => $apprenant->id,
            'classe_id' => $apprenant->classe_id,
            'moyenne' => Evaluation::calculerMoyenne((float) $data['note1'], (float) $data['note2'], (float) $data['composition']),
            'saisi_par' => $request->user()->id,
        ]);

        return back()->with('success', 'Évaluation enregistrée.');
    }

    public function destroy(Apprenant $apprenant, Evaluation $evaluation): RedirectResponse
    {
        $this->exigerPerimetre($apprenant->structure, 'apprenants', $apprenant->id);
        abort_unless($evaluation->apprenant_id === $apprenant->id, 404);
        $evaluation->delete();

        return back()->with('success', 'Évaluation supprimée.');
    }
}
