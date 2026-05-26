<?php
namespace App\Http\Controllers;

use App\Models\StatistiqueAgent;
use Illuminate\Http\Request;

class DashboardAgentController extends Controller
{
    //  TABLEAU DE BORD COMPLET
    public function index(Request $request)
    {
        $agent = $request->user();
        $mois  = now()->month;
        $annee = now()->year;

        // Statistiques du mois
        $statMois = StatistiqueAgent::where('id_agent', $agent->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        // Annonces actives
        $annoncesActives = $agent->annonces()
            ->where('statut', 'active')
            ->count();

        // Contacts non lus
        $contactsNonLus = $agent->contacts()
            ->where('statut', 'non_lu')
            ->count();

        // 5 dernières annonces
        $dernieresAnnonces = $agent->annonces()
            ->with('photoPrincipale', 'localisation', 'categorie')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 5 derniers contacts
        $derniersContacts = $agent->contacts()
            ->with('utilisateur', 'annonce')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Statistiques des 6 derniers mois
        $stats6Mois = StatistiqueAgent::where('id_agent', $agent->id)
            ->orderBy('annee', 'desc')
            ->orderBy('mois', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'agent'              => $agent->load('plan'),
            'statistiques_mois'  => $statMois,
            'annonces_actives'   => $annoncesActives,
            'contacts_non_lus'   => $contactsNonLus,
            'dernieres_annonces' => $dernieresAnnonces,
            'derniers_contacts'  => $derniersContacts,
            'stats_6_mois'       => $stats6Mois,
        ]);
    }

    //  LISTE DES UTILISATEURS QUI ONT CONTACTÉ L'AGENT
    public function utilisateursContacts(Request $request)
    {
        $utilisateurs = $request->user()
            ->contacts()
            ->with('utilisateur')
            ->select('id_user')
            ->distinct()
            ->get()
            ->pluck('utilisateur');

        return response()->json([
            'total'        => $utilisateurs->count(),
            'utilisateurs' => $utilisateurs,
        ]);
    }
}