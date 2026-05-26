<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FavoriController extends Controller
{
    // Liste des favoris de l'utilisateur
    public function index(Request $request)
    {
        $favoris = Favori::with([
                'annonce.localisation',
                'annonce.categorie',
                'annonce.photoPrincipale',
                'annonce.agent',
            ])
            ->where('id_utilisateur', auth('utilisateur')->id())
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json($favoris);
    }

    // Ajouter ou retirer un favori (toggle)
    public function toggle(Request $request, int $idAnnonce)
    {
        $idUtilisateur = auth('utilisateur')->id();

        $favori = Favori::where('id_utilisateur', $idUtilisateur)
            ->where('id_annonce', $idAnnonce)
            ->first();

        if ($favori) {
            $favori->delete();
            return response()->json([
                'message'   => 'Annonce retiree des favoris.',
                'est_favori'=> false,
            ]);
        }

        Favori::create([
            'id_utilisateur' => $idUtilisateur,
            'id_annonce'     => $idAnnonce,
        ]);

        return response()->json([
            'message'    => 'Annonce ajoutee aux favoris.',
            'est_favori' => true,
        ]);
    }

    // Vérifier si une annonce est en favori
    public function verifier(Request $request, int $idAnnonce)
    {
        $estFavori = Favori::where(
                'id_utilisateur', auth('utilisateur')->id()
            )
            ->where('id_annonce', $idAnnonce)
            ->exists();

        return response()->json(['est_favori' => $estFavori]);
    }

    //  Supprimer tous les favoris
    public function vider(Request $request)
    {
        Favori::where('id_utilisateur', auth('utilisateur')->id())
            ->delete();

        return response()->json([
            'message' => 'Favoris vides avec succes.',
        ]);
    }
}
