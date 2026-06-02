<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favori;

class FavoriController extends Controller
{
    private function userId()
    {
        return auth('utilisateur')->id()
            ?? auth('agent')->id();
    }

    public function mesIds()
    {
        $ids = Favori::where('user_id', $this->userId()) // ✅
            ->pluck('annonce_id');
        return response()->json(['ids' => $ids]);
    }

    public function index()
    {
        $favoris = Favori::with([
                'annonce.localisation',
                'annonce.categorie',
                'annonce.photoPrincipale',
            ])
            ->where('user_id', $this->userId()) // ✅
            ->orderByDesc('created_at')
            ->get();
        return response()->json($favoris);
    }

    public function toggle(Request $request)
    {
        $request->validate(['annonce_id' => 'required|integer']);
        $userId    = $this->userId();
        $annonceId = $request->annonce_id;

        $favori = Favori::where('user_id', $userId)  // ✅
            ->where('annonce_id', $annonceId)
            ->first();

        if ($favori) {
            $favori->delete();
            return response()->json(['favori' => false]);
        }

        Favori::create([
            'user_id'    => $userId,  
            'annonce_id' => $annonceId,
        ]);
        return response()->json(['favori' => true]);
    }

    public function viderTout()
    {
        Favori::where('user_id', $this->userId())->delete(); // ✅
        return response()->json(['message' => 'Favoris supprimés.']);
    }
}