<?php

namespace App\Http\Controllers;

use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Facilitateur;
use App\Models\Structure;
use App\Services\SelectionService;
use App\Services\StructureStateMachine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Tableau de bord calculé sur le seul périmètre de l'utilisateur. */
class DashboardController extends Controller
{
    public function __invoke(SelectionService $selection): Response
    {
        $user = request()->user();
        $structures = Structure::visiblePour($user);
        $apprenants = Apprenant::visiblePour($user);

        $listeApprenants = (clone $apprenants)->get();
        $eligibles = $listeApprenants->filter(fn ($a) => $selection->motifExclusion($a, $a->date_naissance ? $a->date_naissance->diffInDays(Carbon::today()) / 365.25 : null) === null)->count();
        $enClasse = $listeApprenants->whereNotNull('classe_id')->count();
        $evalues = Evaluation::whereIn('apprenant_id', $listeApprenants->pluck('id'))->distinct()->count('apprenant_id');

        $parEtat = (clone $structures)->select('etat', DB::raw('count(*) as total'))->groupBy('etat')->pluck('total', 'etat');
        $classes = Classe::visiblePour($user);
        $parNiveau = (clone $classes)->select('niveau', DB::raw('count(*) as total'))->groupBy('niveau')->pluck('total', 'niveau');
        $moyenne = Evaluation::whereIn('apprenant_id', $listeApprenants->pluck('id'))->avg('moyenne');

        return Inertia::render('Tableau/Index', [
            'chiffres' => [
                'structures' => (clone $structures)->count(),
                'classes' => (clone $classes)->count(),
                'facilitateurs' => Facilitateur::visiblePour($user)->count(),
                'apprenants' => $listeApprenants->count(),
                'filles' => $listeApprenants->where('sexe', 'F')->count(),
                'vulnerables' => $listeApprenants->filter->estVulnerable()->count(),
                'moyenne' => $moyenne ? round((float) $moyenne, 2) : null,
            ],
            'parcours' => [
                ['etape' => 'Recensés', 'valeur' => $listeApprenants->count()],
                ['etape' => 'Éligibles', 'valeur' => $eligibles],
                ['etape' => 'En classe', 'valeur' => $enClasse],
                ['etape' => 'Évalués', 'valeur' => $evalues],
                ['etape' => 'Insérés', 'valeur' => null],
            ],
            'parEtat' => collect(StructureStateMachine::LIBELLES)->map(fn ($libelle, $etat) => ['etat' => $etat, 'libelle' => $libelle, 'total' => (int) ($parEtat[$etat] ?? 0)])->values(),
            'parNiveau' => collect(Classe::NIVEAUX)->map(fn ($n) => ['niveau' => $n, 'total' => (int) ($parNiveau[$n] ?? 0)]),
            'dernieres' => (clone $structures)->with('typeOffre')->latest()->limit(5)->get()->map(fn ($s) => [
                'id' => $s->id, 'code' => $s->code_structure, 'nom' => $s->nom_structure, 'ief' => $s->ief, 'etat' => $s->etat,
            ]),
        ]);
    }
}
