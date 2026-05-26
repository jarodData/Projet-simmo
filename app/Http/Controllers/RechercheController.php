<?php
namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\HistoriqueRecherche;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    //  Recherche avec tous les filtres
    public function rechercher(Request $request)
    {
        $query = Annonce::with(
                'localisation',
                'categorie',
                'photoPrincipale',
                'agent'
            )
            ->where('statut', 'active');

        // ── Filtre mot clé ───────────────────
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titre', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // ── Filtre type transaction ──────────
        if ($request->filled('type_transaction')) {
            $query->where(
                'type_transaction',
                $request->type_transaction
            );
        }

        // ── Filtre catégorie ─────────────────
        if ($request->filled('type_bien')) {
            $query->whereHas('categorie', function ($q) use ($request) {
                $q->where('libelle', $request->type_bien);
            });
        }

        // ── Filtre prix ──────────────────────
        if ($request->filled('prix_min')) {
            $query->where('prix', '>=', $request->prix_min);
        }
        if ($request->filled('prix_max')) {
            $query->where('prix', '<=', $request->prix_max);
        }

        // ── Filtre ville ─────────────────────
        if ($request->filled('ville')) {
            $query->whereHas('localisation', function ($q) use ($request) {
                $q->where('ville', 'like', "%{$request->ville}%");
            });
        }

        // ── Filtre quartier ──────────────────
        if ($request->filled('quartier')) {
            $query->whereHas('localisation', function ($q) use ($request) {
                $q->where('quartier', 'like', "%{$request->quartier}%");
            });
        }

        // ── Filtre surface ───────────────────
        if ($request->filled('surface_min')) {
            $query->where('surface_m2', '>=', $request->surface_min);
        }

        // ── Filtre chambres ──────────────────
        if ($request->filled('nb_chambres')) {
            $query->where('nb_chambres', '>=', $request->nb_chambres);
        }

        // ── Filtre meublé ────────────────────
        if ($request->filled('meuble')) {
            $query->where('meuble', (bool) $request->meuble);
        }

        // ── Tri ──────────────────────────────
        match ($request->get('tri', 'recent')) {
            'prix_asc'  => $query->orderBy('prix', 'asc'),
            'prix_desc' => $query->orderBy('prix', 'desc'),
            'populaire' => $query->orderBy('vues', 'desc'),
            default     => $query->orderBy('created_at', 'desc'),
        };

        $resultats = $query->paginate(12);

        // Sauvegarder historique seulement si connecté
    try {
        $utilisateur = auth('utilisateur')->user();
        if ($utilisateur) {
            \App\Models\HistoriqueRecherche::create([
                'id_user'           => $utilisateur->id,
                'terme_recherche'   => $request->q,
                'filtres_appliques' => $request->except(['page']),
                'nb_resultats'      => $resultats->total(),
            ]);
        }
    } catch (\Exception $e) {
        // Ne pas bloquer si historique échoue
    }

    return response()->json($resultats);
    }

    //  Historique de recherche de l'utilisateur
    public function historique(Request $request)
    {
        $historique = HistoriqueRecherche::where(
                'id_user', auth('utilisateur')->id()
            )
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($historique);
    }

    //  Supprimer tout l'historique
    public function supprimerHistorique()
    {
        HistoriqueRecherche::where(
            'id_user', auth('utilisateur')->id()
        )->delete();

        return response()->json([
            'message' => 'Historique supprimé avec succès.',
        ]);
    }
}