<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\AgentImmobilier;
use App\Models\Utilisateur;
use App\Models\Annonce;

class SimmoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. AGENTS ──────────────────────────────
        $agents = [
            ['prenom'=>'Jean','nom'=>'Mbarga','email'=>'jean.mbarga@simmo.cm','telephone'=>'677123456','ville'=>'Yaoundé'],
            ['prenom'=>'Marie','nom'=>'Fotso','email'=>'marie.fotso@simmo.cm','telephone'=>'699234567','ville'=>'Douala'],
            ['prenom'=>'Paul','nom'=>'Nguema','email'=>'paul.nguema@simmo.cm','telephone'=>'655345678','ville'=>'Douala'],
            ['prenom'=>'Alice','nom'=>'Biya','email'=>'alice.biya@simmo.cm','telephone'=>'677456789','ville'=>'Yaoundé'],
            ['prenom'=>'Robert','nom'=>'Kamga','email'=>'robert.kamga@simmo.cm','telephone'=>'699567890','ville'=>'Bafoussam'],
            ['prenom'=>'Sophie','nom'=>'Atangana','email'=>'sophie.atangana@simmo.cm','telephone'=>'655678901','ville'=>'Yaoundé'],
            ['prenom'=>'Marc','nom'=>'Essomba','email'=>'marc.essomba@simmo.cm','telephone'=>'677789012','ville'=>'Douala'],
            ['prenom'=>'Claire','nom'=>'Nkomo','email'=>'claire.nkomo@simmo.cm','telephone'=>'699890123','ville'=>'Garoua'],
            ['prenom'=>'David','nom'=>'Tchio','email'=>'david.tchio@simmo.cm','telephone'=>'655901234','ville'=>'Bamenda'],
            ['prenom'=>'Fatima','nom'=>'Moussa','email'=>'fatima.moussa@simmo.cm','telephone'=>'677012345','ville'=>'Ngaoundéré'],
        ];

        $agentIds = [];
        foreach ($agents as $a) {
            $agent = AgentImmobilier::firstOrCreate(
                ['email' => $a['email']],
                [
                    'prenom'          => $a['prenom'],
                    'nom'             => $a['nom'],
                    'telephone'       => $a['telephone'],
                    'mot_de_passe_hash' => Hash::make('password123'),
                    'statut'          => 'actif',
                    'is_verified'     => true,
                ]
            );
            $agentIds[] = $agent->id;
        }

        echo "✅ " . count($agentIds) . " agents créés\n";

        // ── 2. UTILISATEURS ─────────────────────────
        $utilisateurs = [
            ['prenom'=>'Abdoulaye','nom'=>'Koné','email'=>'abdoulaye@test.cm','type'=>'etudiant'],
            ['prenom'=>'Amina','nom'=>'Diallo','email'=>'amina@test.cm','type'=>'famille'],
            ['prenom'=>'Bruno','nom'=>'Tanga','email'=>'bruno@test.cm','type'=>'professionnel'],
            ['prenom'=>'Cécile','nom'=>'Mvogo','email'=>'cecile@test.cm','type'=>'famille'],
            ['prenom'=>'Daniel','nom'=>'Eto','email'=>'daniel@test.cm','type'=>'etudiant'],
            ['prenom'=>'Esther','nom'=>'Ndem','email'=>'esther@test.cm','type'=>'professionnel'],
            ['prenom'=>'François','nom'=>'Bella','email'=>'francois@test.cm','type'=>'etudiant'],
            ['prenom'=>'Grace','nom'=>'Owona','email'=>'grace@test.cm','type'=>'famille'],
            ['prenom'=>'Henri','nom'=>'Zang','email'=>'henri@test.cm','type'=>'professionnel'],
            ['prenom'=>'Isabelle','nom'=>'Mendo','email'=>'isabelle@test.cm','type'=>'etudiant'],
        ];

        foreach ($utilisateurs as $u) {
            Utilisateur::firstOrCreate(
                ['email' => $u['email']],
                [
                    'prenom'            => $u['prenom'],
                    'nom'               => $u['nom'],
                    'mot_de_passe_hash' => Hash::make('password123'),
                    'type_user'         => $u['type'],
                    'is_verified'       => true,
                ]
            );
        }

        echo "✅ " . count($utilisateurs) . " utilisateurs créés\n";

        // ── 3. ANNONCES ─────────────────────────────
        $villes = [
            'Yaoundé'    => ['Bastos','Nlongkak','Melen','Essos','Biyem-Assi','Nsimeyong','Omnisports','Mvog-Ada'],
            'Douala'     => ['Bonanjo','Akwa','Makepe','Bonapriso','Kotto','Logpom','Ndokoti','Bepanda'],
            'Bafoussam'  => ['Banengo','Djeleng','Famla','Ngomgham'],
            'Garoua'     => ['Marouaré','Plateau','Foulbéré'],
            'Bamenda'    => ['Mile 4','Up Station','Commercial Avenue'],
            'Ngaoundéré' => ['Centre','Dang','Baladji'],
        ];

        $categories = [
            'appartement' => [1,2,3,4],
            'studio'      => [1],
            'villa'       => [3,4,5,6],
            'chambre'     => [1],
            'bureau'      => [1,2,3],
        ];

        $prixLocation = [
            'chambre'     => [25000, 80000],
            'studio'      => [50000, 150000],
            'appartement' => [80000, 350000],
            'villa'       => [200000, 800000],
            'bureau'      => [100000, 500000],
        ];

        $prixVente = [
            'chambre'     => [2000000, 8000000],
            'studio'      => [5000000, 15000000],
            'appartement' => [8000000, 35000000],
            'villa'       => [20000000, 120000000],
            'bureau'      => [10000000, 50000000],
        ];

        $photos = [
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800',
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800',
            'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=800',
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800',
            'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800',
            'https://images.unsplash.com/photo-1582407947304-fd86f28320f3?w=800',
            'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800',
            'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=800',
            'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
            'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800',
        ];

        $descriptions = [
            'appartement' => "Bel appartement moderne bien situé dans un quartier calme et sécurisé. Proche des commodités : marchés, écoles et transports en commun. Idéal pour une famille ou un professionnel.",
            'studio'      => "Studio meublé et équipé, idéal pour étudiant ou jeune professionnel. Cuisine américaine, salle de bain moderne. Quartier dynamique avec toutes commodités à proximité.",
            'villa'       => "Magnifique villa avec jardin dans un quartier résidentiel prisé. Spacieuse et lumineuse avec parking sécurisé. Proche des grandes écoles et centres commerciaux.",
            'chambre'     => "Chambre meublée dans une maison calme. Accès cuisine et salle de bain partagés. Idéale pour étudiant. Quartier bien desservi par les transports.",
            'bureau'      => "Bureau moderne en plein centre d'affaires. Climatisé, sécurisé, parking disponible. Idéal pour entreprise ou profession libérale.",
        ];

        // Récupère les IDs de catégories et localisations
        $catIds = DB::table('categories_bien')->pluck('id', 'libelle');
        $count  = 0;

        foreach ($villes as $ville => $quartiers) {
            foreach ($categories as $typeBien => $nbChambresOptions) {
                // Génère 8-12 annonces par ville/catégorie
                $nbAnnonces = rand(8, 12);

                for ($i = 0; $i < $nbAnnonces; $i++) {
                    $quartier        = $quartiers[array_rand($quartiers)];
                    $typeTransaction = rand(0, 2) === 0 ? 'vente' : 'location';
                    $nbChambres      = $nbChambresOptions[array_rand($nbChambresOptions)];
                    $surface         = rand(20, 200);
                    $meuble          = rand(0, 1);
                    $agentId         = $agentIds[array_rand($agentIds)];

                    // Prix selon transaction
                    $range = $typeTransaction === 'location'
                        ? $prixLocation[$typeBien]
                        : $prixVente[$typeBien];
                    $prix = rand($range[0] / 1000, $range[1] / 1000) * 1000;

                    // Coordonnées approximatives
                    $coords = [
                        'Yaoundé'    => [3.848, 11.502],
                        'Douala'     => [4.049, 9.761],
                        'Bafoussam'  => [5.478, 10.417],
                        'Garoua'     => [9.301, 13.397],
                        'Bamenda'    => [5.960, 10.145],
                        'Ngaoundéré' => [7.327, 13.584],
                    ];

                    $coord = $coords[$ville];
                    $lat   = $coord[0] + (rand(-500, 500) / 10000);
                    $lng   = $coord[1] + (rand(-500, 500) / 10000);

                    // Crée localisation
                    $locId = DB::table('localisations')->insertGetId([
                        'ville'    => $ville,
                        'quartier' => $quartier,
                        'latitude' => $lat,
                        'longitude'=> $lng,
                        'adresse_complete' => $quartier . ', ' . $ville . ', Cameroun',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Nom de l'annonce
                    $titreMap = [
                        'appartement' => "Appartement {$nbChambres} pièces à {$quartier} {$ville}",
                        'studio'      => "Studio meublé à {$quartier} {$ville}",
                        'villa'       => "Villa {$nbChambres} chambres à {$quartier} {$ville}",
                        'chambre'     => "Chambre meublée à {$quartier} {$ville}",
                        'bureau'      => "Bureau {$surface}m² à {$quartier} {$ville}",
                    ];

                    $catId = $catIds[$typeBien] ?? 1;

                    // Crée annonce
                    $annonceId = DB::table('annonces')->insertGetId([
                        'titre'            => $titreMap[$typeBien],
                        'description'      => $descriptions[$typeBien],
                        'prix'             => $prix,
                        'surface_m2'       => $surface,
                        'nb_chambres'      => $nbChambres,
                        'meuble'           => $meuble,
                        'type_transaction' => $typeTransaction,
                        'id_categorie'     => $catId,
                        'id_localisation'  => $locId,
                        'id_agent'         => $agentId,
                        'statut'           => 'active',
                        'vues'             => rand(0, 500),
                        'created_at'       => now()->subDays(rand(0, 90)),
                        'updated_at'       => now(),
                    ]);

                    // Photo principale
                    DB::table('photos_annonces')->insert([
                        'id_annonce'    => $annonceId,
                        'chemin_image'  => $photos[array_rand($photos)],
                        'est_principale'=> 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    $count++;
                }
            }
        }

        echo "✅ {$count} annonces créées\n";
        echo "🎉 Base de données enrichie avec succès !\n";
    }
}