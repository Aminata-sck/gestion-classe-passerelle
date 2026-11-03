<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Consultation de l'historique des actions (lecture seule). */
class AuditController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = AuditLog::query();
        if ($a = $request->input('action')) {
            $query->where('action', $a);
        }
        if ($u = trim((string) $request->input('utilisateur'))) {
            $query->where('user_email', 'like', "%{$u}%");
        }
        if ($d = $request->input('du')) {
            $query->where('created_at', '>=', $d);
        }

        return Inertia::render('Journal/Index', [
            'entrees' => $this->page($query->orderByDesc('id')->paginate(15)->withQueryString(), fn (AuditLog $l) => [
                'id' => $l->id, 'date' => $l->created_at?->format('d/m/Y H:i:s'), 'utilisateur' => $l->user_email ?? '—',
                'action' => $l->action, 'objet' => trim(($l->entity ?? '').' '.($l->entity_id ? '#'.$l->entity_id : '')),
                'avant' => $l->old_values, 'apres' => $l->new_values, 'ip' => $l->ip_address,
            ]),
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'filtres' => $request->only(['action', 'utilisateur', 'du']),
        ]);
    }
}
