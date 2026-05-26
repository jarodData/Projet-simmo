<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // Plans tarifaires
        DB::table('plans')->insert([
            [
                'nom_plan' => 'Starter',
                'prix_mensuel' => 0,
                'duree_essai_jours' => 30,
                'nb_annonces_max' => 5,
                'fonctionnalites' => json_encode(['badge_verifie' => false, 'mise_en_avant' => false]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_plan' => 'Basic',
                'prix_mensuel' => 9900,
                'duree_essai_jours' => 0,
                'nb_annonces_max' => 15,
                'fonctionnalites' => json_encode(['badge_verifie' => true, 'mise_en_avant' => false]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_plan' => 'Pro',
                'prix_mensuel' => 19900,
                'duree_essai_jours' => 0,
                'nb_annonces_max' => 50,
                'fonctionnalites' => json_encode(['badge_verifie' => true, 'mise_en_avant' => true]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_plan' => 'Premium',
                'prix_mensuel' => 35000,
                'duree_essai_jours' => 0,
                'nb_annonces_max' => 999,
                'fonctionnalites' => json_encode(['badge_verifie' => true, 'mise_en_avant' => true, 'support_prioritaire' => true]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);


        // ─────────────────────────────────────
        // CATEGORIES
        // ─────────────────────────────────────

        DB::table('categories_bien')->insert([

            [
                'libelle'    => 'Appartement',
                'description'=> 'Appartement moderne',
                'icone'      => 'building',
            ],

            [
                'libelle'    => 'Studio',
                'description'=> 'Studio meublé',
                'icone'      => 'house',
            ],

            [
                'libelle'    => 'Villa',
                'description'=> 'Villa familiale',
                'icone'      => 'house-door',
            ],

            [
                'libelle'    => 'Chambre',
                'description'=> 'Chambre étudiant',
                'icone'      => 'door-open',
            ],

        ]);



       

        // ─────────────────────────────────────
        // AGENTS
        // ─────────────────────────────────────

        for($i = 1; $i <= 10; $i++){

            DB::table('agents_immobiliers')->insert([

                'nom'      => 'Agent'.$i,
                'prenom'   => 'SIMMo'.$i,
                'email'    => 'agent'.$i.'@gmail.com',

                'mot_de_passe_hash' => Hash::make('password'),

                'telephone' => '69000000'.$i,
                'numero_agence' => 'SIMMo'.$i,

                'statut' => 'actif',
                'token_verification' => null,

                'is_verified' => true,

                'id_plan' => rand(1,2),

                'date_souscription' => now(),

                'date_expiration_plan' =>
                    now()->addMonths(1),

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // ─────────────────────────────────────
        // UTILISATEURS
        // ─────────────────────────────────────

        for($i = 1; $i <= 20; $i++){

            DB::table('utilisateurs')->insert([

                'nom'     => 'User'.$i,

                'prenom'  => 'Client'.$i,

                'email'   => 'user'.$i.'@gmail.com',

                'mot_de_passe_hash' =>
                    Hash::make('password'),

                'telephone' =>
                    '68000000'.$i,

                'type_user' =>
                    ['etudiant','famille','professionnel']
                    [rand(0,2)],

                'is_verified' => true,

                'token_verification' => null,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // ─────────────────────────────────────
        // LOCALISATIONS
        // ─────────────────────────────────────

        $quartiers = [

            // DOUALA
            ['Douala','Bonamoussadi'],
            ['Douala','Makepe'],
            ['Douala','Akwa'],
            ['Douala','PK14'],
            ['Douala','Logpom'],
            ['Douala','Yassa'],
            ['Douala','Japoma'],

            // YAOUNDE
            ['Yaoundé','Bastos'],
            ['Yaoundé','Emana'],
            ['Yaoundé','Obili'],
            ['Yaoundé','Ngoa-Ekelle'],
            ['Yaoundé','Essos'],
            ['Yaoundé','Mvog-Ada'],

            // BUEA
            ['Buea','Molyko'],
            ['Buea','Bonduma'],

        ];

        foreach($quartiers as $q){

            DB::table('localisations')->insert([

                'pays' => 'Cameroun',

                'ville' => $q[0],

                'quartier' => $q[1],

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // ─────────────────────────────────────
        // ANNONCES
        // ─────────────────────────────────────

        $titres = [

            'Studio moderne',
            'Appartement meublé',
            'Villa haut standing',
            'Chambre étudiant',
            'Mini studio sécurisé',
            'Appartement VIP',
            'Maison familiale',
            'Studio climatisé',

        ];

        $descriptions = [

            'Logement moderne proche du goudron.',
            'Appartement sécurisé avec parking.',
            'Chambre propre et accessible.',
            'Villa avec forage et gardien.',
            'Résidence calme et sécurisée.',
            'Idéal pour étudiants et familles.',
            'Très bon voisinage.',
            'Accès facile et eau permanente.',

        ];

        for($i = 1; $i <= 100; $i++){

            DB::table('annonces')->insert([

                'titre' =>
                    $titres[array_rand($titres)],

                'description' =>
                    $descriptions[array_rand($descriptions)],

                'prix' =>
                    rand(30000, 500000),

                'surface_m2' =>
                    rand(20, 250),

                'nb_pieces' =>
                    rand(1, 8),

                'nb_chambres' =>
                    rand(1, 5),

                'nb_salles_bain' =>
                    rand(1, 4),

                'type_transaction' =>
                    rand(0,1)
                    ? 'location'
                    : 'vente',

                'statut' => 'active',

                'meuble' =>
                    rand(0,1),

                'vues' =>
                    rand(0,500),

                'score_ia' =>
                    rand(0,100),

                'id_agent' =>
                    rand(1,10),

                'id_categorie' =>
                    rand(1,4),

                'id_localisation' =>
                    rand(1,15),

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // ─────────────────────────────────────
        // PHOTOS
        // ─────────────────────────────────────

        for($i = 1; $i <= 100; $i++){

            DB::table('photos_annonces')->insert([

                'id_annonce' => $i,

                'chemin_image' =>
                    'annonces/photos/demo'.rand(1,8).'.jpg',

                'est_principale' => true,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // ─────────────────────────────────────
        // CONVERSATIONS
        // ─────────────────────────────────────

        for($i = 1; $i <= 30; $i++){

            DB::table('conversations')->insert([

                'id_utilisateur' =>
                    rand(1,20),

                'id_agent' =>
                    rand(1,10),

                'dernier_message' =>
                    'Bonjour je suis intéressé.',

                'dernier_message_at' =>
                    now(),

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // ─────────────────────────────────────
        // MESSAGES
        // ─────────────────────────────────────

        for($i = 1; $i <= 100; $i++){

            DB::table('messagess')->insert([

                'id_conversation' =>
                    rand(1,30),

                'sender_type' =>
                    rand(0,1)
                    ? 'utilisateur'
                    : 'agent',

                'sender_id' =>
                    rand(1,20),

                'contenu' =>
                    'Bonjour, ce bien est-il disponible ?',

                'lu' =>
                    rand(0,1),

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
