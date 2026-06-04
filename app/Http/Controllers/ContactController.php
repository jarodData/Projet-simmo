<?php
namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Annonce;
use App\Models\StatistiqueAgent;
use Illuminate\Http\Request;
// app/Http/Controllers/ContactController.php
use Illuminate\Support\Facades\Mail;
use App\Mail\MessageAgent;

class ContactController extends Controller
{
    //  CONTACTER UN AGENT (Utilisateur)
    public function contacterAgent(Request $request)
    {
        $request->validate([
            'id_annonce' => 'required|exists:annonces,id',
            'message'    => 'required|string|max:1000',
        ]);

        $annonce = Annonce::findOrFail($request->id_annonce);

        // Vérifier que l'utilisateur n'a pas déjà contacté pour cette annonce
        $dejaContacte = Contact::where('id_user', $request->user()->id)
            ->where('id_annonce', $request->id_annonce)
            ->exists();

        if ($dejaContacte) {
            return response()->json([
                'message' => 'Vous avez déjà contacté cet agent pour cette annonce.',
            ], 409);
        }

        $contact = Contact::create([
            'id_user'    => $request->user()->id,
            'id_agent'   => $annonce->id_agent,
            'id_annonce' => $request->id_annonce,
            'message'    => $request->message,
            'statut'     => 'non_lu',
        ]);

        // Mettre à jour les statistiques de l'agent
        $mois  = now()->month;
        $annee = now()->year;

        $stat = StatistiqueAgent::firstOrCreate(
            ['id_agent' => $annonce->id_agent, 'mois' => $mois, 'annee' => $annee],
            ['nb_annonces_publiees' => 0, 'nb_contacts_recus' => 0,
             'nb_services_rendus' => 0, 'nb_vues_total' => 0]
        );
        $stat->increment('nb_contacts_recus');

        return response()->json([
            'message' => 'Message envoyé avec succès à l\'agent !',
            'contact' => $contact,
        ], 201);
    }

    //  LISTE DES CONTACTS REÇUS (Agent)
    public function mesContacts(Request $request)
    {
        $contacts = Contact::where('id_agent', $request->user()->id)
            ->with('utilisateur', 'annonce')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($contacts);
    }

    //  MARQUER UN CONTACT COMME LU (Agent)
    public function marquerLu(Request $request, $id)
    {
        $contact = Contact::where('id', $id)
            ->where('id_agent', $request->user()->id)
            ->firstOrFail();

        $contact->update(['statut' => 'lu']);

        return response()->json(['message' => 'Contact marqué comme lu.']);
    }

    //  CONTACTS DE L'UTILISATEUR
    public function mesContactsUtilisateur(Request $request)
    {
        $contacts = Contact::where('id_user', $request->user()->id)
            ->with('annonce.photoPrincipale', 'agent')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($contacts);
    }

    

public function envoyerMessage(Request $request)
{
    $data = $request->validate([
        'prenom'  => 'required|string',
        'nom'     => 'required|string',
        'email'   => 'required|email',
        'sujet'   => 'required|string',
        'message' => 'required|string|max:1000',
        'copie'   => 'boolean',
    ]);

    Mail::to('agent@simmo.cm')->send(new MessageAgent($data));

    if ($data['copie'] ?? false) {
        Mail::to($data['email'])->send(new MessageAgent($data));
    }

    return response()->json(['message' => 'Envoyé avec succès.']);
}
}