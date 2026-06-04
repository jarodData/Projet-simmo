<?php
// app/Http/Controllers/Admin/CniController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CniUploadRequest;
use App\Models\Agent;
use App\Services\CniVerificateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CniController extends Controller
{
    public function __construct(private CniVerificateur $verificateur) {}

    // ────────────────────────────────────────────────
    //  GET /admin/cni
    //  Liste de tous les agents + statut CNI
    // ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $statut = $request->get('statut', 'tous');

        $query = Agent::query()->latest();

        match ($statut) {
            'en_attente' => $query->enAttenteCni(),
            'approuve'   => $query->cniApprouvees(),
            'rejete'     => $query->cniRejetees(),
            'manuel'     => $query->verifManuelle(),
            default      => null,
        };

        $agents = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => Agent::count(),
            'en_attente'  => Agent::enAttenteCni()->count(),
            'approuvees'  => Agent::cniApprouvees()->count(),
            'rejetees'    => Agent::cniRejetees()->count(),
            'manuelles'   => Agent::verifManuelle()->count(),
        ];

        return view('admin.cni.index', compact('agents', 'stats', 'statut'));
    }

    // ────────────────────────────────────────────────
    //  GET /admin/cni/{agent}
    //  Détail d'un agent + résultat CNI
    // ────────────────────────────────────────────────

    public function show(Agent $agent)
    {
        return view('admin.cni.show', compact('agent'));
    }

    // ────────────────────────────────────────────────
    //  GET /admin/cni/verifier/{agent?}
    //  Interface drag & drop
    // ────────────────────────────────────────────────

    public function verifier(?Agent $agent = null)
    {
        $agents = Agent::select('id', 'prenom', 'nom', 'statut')->orderBy('nom')->get();
        return view('admin.cni.verifier', compact('agent', 'agents'));
    }

    // ────────────────────────────────────────────────
    //  POST /admin/cni/analyser
    //  Analyse sans sauvegarder (test rapide)
    // ────────────────────────────────────────────────

    public function analyser(CniUploadRequest $request)
    {
        $resultat = $this->verificateur->analyser($request->file('fichier'));

        if (! $resultat['succes']) {
            return response()->json(['erreur' => $resultat['erreur']], 500);
        }

        return response()->json($resultat);
    }

    // ────────────────────────────────────────────────
    //  POST /admin/cni/valider/{agent}
    //  Analyse + sauvegarde en base + mise à jour statut
    // ────────────────────────────────────────────────

    public function valider(CniUploadRequest $request, Agent $agent)
    {
        $fichier = $request->file('fichier');

        // 1. Sauvegarder le fichier
        $chemin = $fichier->storeAs(
            'cni/' . $agent->id,
            'cni_' . now()->format('Ymd_His') . '.' . $fichier->extension(),
            'private'
        );
        $agent->cni_chemin = $chemin;
        $agent->save();

        // 2. Analyser avec l'IA
        $resultat = $this->verificateur->analyser($fichier);

        if (! $resultat['succes']) {
            return response()->json(['erreur' => $resultat['erreur']], 500);
        }

        // 3. Appliquer le résultat sur l'agent (save inclus)
        $agent->appliquerResultatIa($resultat);

        return response()->json([
            'succes'         => true,
            'id_agent'       => $agent->id,
            'recommandation' => $agent->cni_recommandation,
            'statut_agent'   => $agent->statut,
            'analyse'        => $resultat['analyse'],
        ]);
    }

    // ────────────────────────────────────────────────
    //  POST /admin/cni/decision/{agent}
    //  Override manuel par un admin
    // ────────────────────────────────────────────────

    public function decision(Request $request, Agent $agent)
    {
        $request->validate([
            'action' => 'required|in:approuver,rejeter',
            'motif'  => 'nullable|string|max:500',
        ]);

        $agent->decisionAdmin(
            action  : $request->action,
            motif   : $request->motif ?? 'Décision manuelle admin',
            adminId : auth()->id(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'succes'         => true,
                'id_agent'       => $agent->id,
                'action'         => $request->action,
                'nouveau_statut' => $agent->statut,
            ]);
        }

        return redirect()
            ->route('admin.cni.show', $agent)
            ->with('success', "Décision « {$request->action} » enregistrée.");
    }

    // ────────────────────────────────────────────────
    //  GET /admin/cni/image/{agent}
    //  Servir l'image CNI (stockage privé)
    // ────────────────────────────────────────────────

    public function image(Agent $agent)
    {
        if (! $agent->cni_chemin || ! Storage::disk('private')->exists($agent->cni_chemin)) {
            abort(404, 'Image CNI introuvable');
        }

        return response()->file(
            Storage::disk('private')->path($agent->cni_chemin)
        );
    }

    // ────────────────────────────────────────────────
    //  GET /admin/cni/stats  (API JSON)
    // ────────────────────────────────────────────────

    public function stats()
    {
        return response()->json([
            'total'            => Agent::count(),
            'cni_soumises'     => Agent::whereNotNull('cni_chemin')->count(),
            'cni_approuvees'   => Agent::cniApprouvees()->count(),
            'cni_rejetees'     => Agent::cniRejetees()->count(),
            'cni_en_attente'   => Agent::enAttenteCni()->count(),
            'cni_manuelles'    => Agent::verifManuelle()->count(),
            'taux_approbation' => Agent::whereNotNull('cni_chemin')->count() > 0
                ? round(Agent::cniApprouvees()->count() / Agent::whereNotNull('cni_chemin')->count() * 100, 1)
                : 0,
        ]);
    }
}
