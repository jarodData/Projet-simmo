<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\CategorieBien;
use App\Models\AgentImmobilier;
use App\Models\Localisation;
use App\Models\Annonce;
use App\Models\PhotoAnnonce;

class AnnonceSeeder extends Seeder
{
    // ============================================
    // Images par categorie depuis Picsum Photos
    // IDs d'images qui ressemblent a des biens
    // ============================================
    private array $imagesParCategorie = [
        'appartement' => [
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800',
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800',
            'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800',
            'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800',
        ],
        'studio' => [
            'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800',
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800',
            'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?w=800',
            'https://images.unsplash.com/photo-1631679706909-1844bbd07221?w=800',
        ],
        'villa' => [
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800',
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800',
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800',
            'https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83?w=800',
        ],
        'chambre' => [
            'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800',
            'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800',
            'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800',
        ],
        'bureau' => [
            'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
            'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=800',
            'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800',
            'https://images.unsplash.com/photo-1568992687947-868a62a9f521?w=800',
        ],
        'terrain' => [
            'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800',
            'https://images.unsplash.com/photo-1628744448840-55bdb2497bd4?w=800',
            'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800',
        ],
    ];

    // Compteurs par categorie pour rotation des images
    private array $compteurs = [
        'appartement' => 0,
        'studio'      => 0,
        'villa'       => 0,
        'chambre'     => 0,
        'bureau'      => 0,
        'terrain'     => 0,
    ];

    public function run(): void
    {
        // S'assurer que le dossier existe
        Storage::disk('public')->makeDirectory('annonces/photos');

        $agents       = AgentImmobilier::all()->pluck('id')->toArray();
        $categorieIds = CategorieBien::pluck('id', 'libelle');

        if (empty($agents)) {
            $this->command->error(
                'Aucun agent trouve. Lancez d\'abord DatabaseSeeder.'
            );
            return;
        }

        $toutesLesAnnonces = array_merge(
            $this->annoncesDouala(),
            $this->annoncesYaounde(),
            $this->annoncesAutresVilles()
        );

        $nbCrees = 0;

        foreach ($toutesLesAnnonces as $i => $d) {
            try {
                $localisation = Localisation::create([
                    'pays'             => 'Cameroun',
                    'ville'            => $d['ville'],
                    'quartier'         => $d['quartier'],
                    'adresse_complete' => $d['adresse'],
                    'latitude'         => $d['latitude'],
                    'longitude'        => $d['longitude'],
                ]);

                $idCategorie = $categorieIds[$d['categorie']] ?? null;
                if (!$idCategorie) {
                    $this->command->warn(
                        'Categorie introuvable : ' . $d['categorie']
                    );
                    continue;
                }

                $annonce = Annonce::create([
                    'titre'            => $d['titre'],
                    'description'      => $d['description'],
                    'prix'             => $d['prix'],
                    'surface_m2'       => $d['surface_m2'],
                    'nb_pieces'        => $d['nb_pieces'],
                    'nb_chambres'      => $d['nb_chambres'],
                    'nb_salles_bain'   => $d['nb_salles_bain'],
                    'type_transaction' => $d['type_transaction'],
                    'meuble'           => $d['meuble'],
                    'statut'           => 'active',
                    'vues'             => $d['vues'],
                    'score_ia'         => round(rand(60, 95) / 100, 2),
                    'id_agent'         => $agents[$i % count($agents)],
                    'id_categorie'     => $idCategorie,
                    'id_localisation'  => $localisation->id,
                ]);

                // ✅ Telecharger et associer les photos
                $this->ajouterPhotos($annonce, $d['categorie'], $d['nb_photos'] ?? 3);

                $nbCrees++;
                $this->command->info(
                    '[' . $nbCrees . '] ' . $d['titre'] . ' - OK'
                );

            } catch (\Exception $e) {
                $this->command->error(
                    'Erreur : ' . $d['titre'] . ' - ' . $e->getMessage()
                );
            }
        }

        $this->command->newLine();
        $this->command->info('================================================');
        $this->command->info('  ANNONCES CREEES   : ' . $nbCrees);
        $this->command->info('  PHOTOS ASSOCIEES  : ' . PhotoAnnonce::count());
        $this->command->info('  TOTAL BD          : ' . Annonce::count());
        $this->command->info('================================================');
    }

    // ============================================
    // TELECHARGER ET ASSOCIER LES PHOTOS
    // ============================================
    private function ajouterPhotos(
        Annonce $annonce,
        string  $categorie,
        int     $nbPhotos = 3
    ): void {
        $urls  = $this->imagesParCategorie[$categorie] ?? $this->imagesParCategorie['appartement'];
        $count = count($urls);

        for ($j = 0; $j < $nbPhotos; $j++) {
            try {
                // Rotation circulaire des images par categorie
                $idx = $this->compteurs[$categorie] % $count;
                $url = $urls[$idx];
                $this->compteurs[$categorie]++;

                // Nom unique du fichier
                $nomFichier = 'annonces/photos/' .
                    Str::uuid() . '.jpg';

                // Telecharger l'image
                $contenu = $this->telechargerImage($url);

                if ($contenu) {
                    // Sauvegarder dans storage/app/public/
                    Storage::disk('public')->put(
                        $nomFichier, $contenu
                    );

                    // Creer l'entree en base
                    PhotoAnnonce::create([
                        'id_annonce'    => $annonce->id,
                        'chemin_image'  => $nomFichier,
                        'est_principale'=> $j === 0,
                        'ordre'         => $j + 1,
                    ]);
                } else {
                    // Fallback — photo placeholder si download echoue
                    $this->ajouterPhotoPlaceholder(
                        $annonce, $categorie, $j
                    );
                }

            } catch (\Exception $e) {
                $this->command->warn(
                    'Photo non telechargee : ' . $e->getMessage()
                );
                $this->ajouterPhotoPlaceholder(
                    $annonce, $categorie, $j
                );
            }
        }
    }

    // ============================================
    // TELECHARGER UNE IMAGE AVEC RETRY
    // ============================================
    private function telechargerImage(string $url): ?string
    {
        $tentatives = 3;

        for ($i = 0; $i < $tentatives; $i++) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'User-Agent' => 'SIMMo-Seeder/1.0',
                    ])
                    ->get($url);

                if ($response->successful()) {
                    return $response->body();
                }

            } catch (\Exception $e) {
                if ($i < $tentatives - 1) {
                    sleep(1);
                }
            }
        }

        return null;
    }

    // ============================================
    // PHOTO PLACEHOLDER SI PAS D'INTERNET
    // ============================================
    private function ajouterPhotoPlaceholder(
        Annonce $annonce,
        string  $categorie,
        int     $ordre
    ): void {
        // Utiliser picsum comme fallback simple
        $urlFallback = 'https://picsum.photos/seed/' .
            $annonce->id . $ordre . '/800/600';

        $nomFichier  = 'annonces/photos/' .
            Str::uuid() . '.jpg';

        try {
            $contenu = Http::timeout(10)->get($urlFallback)->body();
            if ($contenu) {
                Storage::disk('public')->put($nomFichier, $contenu);
                PhotoAnnonce::create([
                    'id_annonce'    => $annonce->id,
                    'chemin_image'  => $nomFichier,
                    'est_principale'=> $ordre === 0,
                    'ordre'         => $ordre + 1,
                ]);
            }
        } catch (\Exception $e) {
            // Ignorer si pas d'internet du tout
        }
    }

    // ============================================
    // ANNONCES DOUALA
    // ============================================
    private function annoncesDouala(): array
    {
        return [
            [
                'titre'            => 'Studio meuble haut standing Akwa',
                'description'      => 'Magnifique studio entierement meuble au coeur du quartier Akwa. Cuisine equipee, climatisation, acces securise 24h/24, wifi inclus. Vue panoramique sur la ville. Ideal pour professionnel ou cadre.',
                'prix'             => 150000,
                'surface_m2'       => 38,
                'nb_pieces'        => 2,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'studio',
                'ville'            => 'Douala',
                'quartier'         => 'Akwa',
                'adresse'          => 'Boulevard de la Liberte Akwa Douala',
                'latitude'         => 4.0511,
                'longitude'        => 9.7028,
                'vues'             => 187,
                'nb_photos'        => 4,
            ],
            [
                'titre'            => 'Appartement F3 centre commercial Akwa',
                'description'      => 'Bel appartement F3 situe au centre des affaires d\'Akwa. Salon spacieux, 2 chambres, cuisine americaine, 2 salles de bain. Immeuble avec ascenseur, gardien, parking securise. Proche de toutes les commodites.',
                'prix'             => 280000,
                'surface_m2'       => 90,
                'nb_pieces'        => 4,
                'nb_chambres'      => 2,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Akwa',
                'adresse'          => 'Rue Joffre Akwa Douala',
                'latitude'         => 4.0520,
                'longitude'        => 9.7040,
                'vues'             => 143,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Bureau moderne Akwa centre affaires',
                'description'      => 'Espace bureau professionnel de 80m2 au coeur du centre des affaires d\'Akwa. Climatise, internet fibre optique, salle de reunion equipee, reception, parking securise.',
                'prix'             => 250000,
                'surface_m2'       => 80,
                'nb_pieces'        => 5,
                'nb_chambres'      => 0,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'bureau',
                'ville'            => 'Douala',
                'quartier'         => 'Akwa',
                'adresse'          => 'Avenue du General de Gaulle Akwa',
                'latitude'         => 4.0515,
                'longitude'        => 9.7020,
                'vues'             => 98,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Appartement luxe Bonanjo vue mer',
                'description'      => 'Exceptionnel appartement de luxe a Bonanjo avec vue imprenable sur la mer. 3 chambres dont suite parentale, double salon, cuisine gastronomique. Residence fermee avec piscine et gym.',
                'prix'             => 650000,
                'surface_m2'       => 150,
                'nb_pieces'        => 6,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 3,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Bonanjo',
                'adresse'          => 'Rue du Commerce Bonanjo Douala',
                'latitude'         => 4.0490,
                'longitude'        => 9.6980,
                'vues'             => 312,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Villa a vendre Bonanjo titre foncier',
                'description'      => 'Superbe villa a vendre dans le quartier residentiel de Bonanjo. 5 chambres, 3 salles de bain, grand salon de reception, cuisine equipee, garage 2 voitures. Titre foncier disponible.',
                'prix'             => 120000000,
                'surface_m2'       => 300,
                'nb_pieces'        => 9,
                'nb_chambres'      => 5,
                'nb_salles_bain'   => 3,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Douala',
                'quartier'         => 'Bonanjo',
                'adresse'          => 'Boulevard de la Reunification Bonanjo',
                'latitude'         => 4.0500,
                'longitude'        => 9.6990,
                'vues'             => 445,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Villa familiale Bonapriso avec piscine',
                'description'      => 'Magnifique villa familiale a Bonapriso avec piscine privee. 4 chambres spacieuses, suite parentale avec dressing, grand salon, salle a manger, cuisine moderne. Quartier calme et securise.',
                'prix'             => 550000,
                'surface_m2'       => 220,
                'nb_pieces'        => 8,
                'nb_chambres'      => 4,
                'nb_salles_bain'   => 3,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'villa',
                'ville'            => 'Douala',
                'quartier'         => 'Bonapriso',
                'adresse'          => 'Rue de la Paix Bonapriso Douala',
                'latitude'         => 4.0310,
                'longitude'        => 9.6920,
                'vues'             => 289,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Appartement 3 chambres Bonapriso',
                'description'      => 'Bel appartement 3 chambres dans une residence fermee a Bonapriso. Salon double, cuisine equipee, 2 salles de bain, balcon. Residence avec piscine et gardien 24h.',
                'prix'             => 320000,
                'surface_m2'       => 105,
                'nb_pieces'        => 5,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Bonapriso',
                'adresse'          => 'Avenue du Bali Bonapriso Douala',
                'latitude'         => 4.0295,
                'longitude'        => 9.6910,
                'vues'             => 201,
                'nb_photos'        => 4,
            ],
            [
                'titre'            => 'Studio neuf Bonapriso jamais habite',
                'description'      => 'Studio neuf de qualite superieure jamais habite a Bonapriso. Parquet flottant, cuisine equipee, climatisation, balcon, parking. Immeuble recemment construit avec gardien.',
                'prix'             => 130000,
                'surface_m2'       => 42,
                'nb_pieces'        => 2,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'studio',
                'ville'            => 'Douala',
                'quartier'         => 'Bonapriso',
                'adresse'          => 'Rue Castelnau Bonapriso Douala',
                'latitude'         => 4.0300,
                'longitude'        => 9.6900,
                'vues'             => 156,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Villa moderne Makepe 4 chambres',
                'description'      => 'Belle villa moderne a Makepe. 4 chambres dont une suite parentale, grand salon, salle a manger, cuisine bien equipee, 2 salles de bain, terrasse, jardin, garage 2 voitures.',
                'prix'             => 400000,
                'surface_m2'       => 180,
                'nb_pieces'        => 7,
                'nb_chambres'      => 4,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Douala',
                'quartier'         => 'Makepe',
                'adresse'          => 'Rue des Palmiers Makepe Douala',
                'latitude'         => 4.0800,
                'longitude'        => 9.7400,
                'vues'             => 234,
                'nb_photos'        => 4,
            ],
            [
                'titre'            => 'Appartement meuble Makepe tout confort',
                'description'      => 'Appartement entierement meuble a Makepe. 2 chambres, salon, cuisine equipee, salle de bain, balcon. Immeuble avec gardien, eau et electricite permanentes. Wifi inclus.',
                'prix'             => 180000,
                'surface_m2'       => 65,
                'nb_pieces'        => 3,
                'nb_chambres'      => 2,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Makepe',
                'adresse'          => 'Carrefour Makepe Douala',
                'latitude'         => 4.0810,
                'longitude'        => 9.7380,
                'vues'             => 178,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Villa a vendre Makepe 5 chambres',
                'description'      => 'Grande villa a vendre a Makepe. 5 chambres, 3 salles de bain, double salon, cuisine moderne, garage, jardin clos. Titre foncier disponible.',
                'prix'             => 75000000,
                'surface_m2'       => 250,
                'nb_pieces'        => 9,
                'nb_chambres'      => 5,
                'nb_salles_bain'   => 3,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Douala',
                'quartier'         => 'Makepe',
                'adresse'          => 'Rue de l\'Esplanade Makepe',
                'latitude'         => 4.0820,
                'longitude'        => 9.7390,
                'vues'             => 389,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Chambre meublee Deido proche marche',
                'description'      => 'Chambre propre et securisee a Deido, a 200m du marche. Eau et electricite inclus, meublee. Environnement anime avec commerces et transports. Ideal pour etudiant ou employe.',
                'prix'             => 30000,
                'surface_m2'       => 14,
                'nb_pieces'        => 1,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'chambre',
                'ville'            => 'Douala',
                'quartier'         => 'Deido',
                'adresse'          => 'Rue du Marche Deido Douala',
                'latitude'         => 4.0700,
                'longitude'        => 9.7300,
                'vues'             => 122,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Appartement F2 Deido bon etat',
                'description'      => 'Appartement F2 en bon etat a Deido. Salon, chambre, cuisine, salle de bain. Eau et electricite disponibles, quartier bien desservi par les transports.',
                'prix'             => 65000,
                'surface_m2'       => 40,
                'nb_pieces'        => 2,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Deido',
                'adresse'          => 'Avenue Poincare Deido Douala',
                'latitude'         => 4.0710,
                'longitude'        => 9.7290,
                'vues'             => 99,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Chambre etudiant New Bell universite',
                'description'      => 'Chambre disponible pour etudiant pres de l\'Universite de Douala. Securisee, eau et electricite, meublee simplement. Proche des transports. Contrat de bail disponible.',
                'prix'             => 25000,
                'surface_m2'       => 12,
                'nb_pieces'        => 1,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'chambre',
                'ville'            => 'Douala',
                'quartier'         => 'New Bell',
                'adresse'          => 'Rue de New Bell Douala',
                'latitude'         => 4.0400,
                'longitude'        => 9.7100,
                'vues'             => 167,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Villa Logpom quartier calme famille',
                'description'      => 'Belle villa dans le quartier residentiel de Logpom. 3 chambres, salon, salle a manger, cuisine, 2 salles de bain, jardin et parking. Quartier tranquille, proche des ecoles et commerces.',
                'prix'             => 280000,
                'surface_m2'       => 140,
                'nb_pieces'        => 6,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Douala',
                'quartier'         => 'Logpom',
                'adresse'          => 'Rue des Fleurs Logpom Douala',
                'latitude'         => 4.0900,
                'longitude'        => 9.7500,
                'vues'             => 203,
                'nb_photos'        => 4,
            ],
            [
                'titre'            => 'Appartement neuf Logpom 3 chambres',
                'description'      => 'Appartement neuf de 3 chambres a Logpom. Finitions modernes, parquet, cuisine equipee, 2 salles de bain, balcon filant, parking. Residence recente avec gardien permanent.',
                'prix'             => 220000,
                'surface_m2'       => 95,
                'nb_pieces'        => 4,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Logpom',
                'adresse'          => 'Avenue de Logpom Douala',
                'latitude'         => 4.0880,
                'longitude'        => 9.7480,
                'vues'             => 145,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Appartement F4 Bali residence fermee',
                'description'      => 'Grand appartement F4 dans une residence securisee a Bali. 3 chambres, salon double, cuisine americaine, 2 salles de bain, terrasse. Piscine, salle de sport, gardien 24h.',
                'prix'             => 350000,
                'surface_m2'       => 120,
                'nb_pieces'        => 5,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Bali',
                'adresse'          => 'Rue de Bali Douala',
                'latitude'         => 4.0600,
                'longitude'        => 9.7100,
                'vues'             => 267,
                'nb_photos'        => 4,
            ],
            [
                'titre'            => 'Villa moderne Kotto piscine privee',
                'description'      => 'Somptueuse villa moderne a Kotto avec piscine privee. 4 chambres dont suite parentale, 2 salons, cuisine haut de gamme, 3 salles de bain, bureau, terrasse. Domaine clos avec gardien.',
                'prix'             => 700000,
                'surface_m2'       => 280,
                'nb_pieces'        => 10,
                'nb_chambres'      => 4,
                'nb_salles_bain'   => 3,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'villa',
                'ville'            => 'Douala',
                'quartier'         => 'Kotto',
                'adresse'          => 'Rue de Kotto Douala',
                'latitude'         => 4.0750,
                'longitude'        => 9.7600,
                'vues'             => 521,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Terrain 600m2 Kotto constructible',
                'description'      => 'Terrain a batir de 600m2 a Kotto. Bien viabilise, eau et electricite en facade, acces route principale bitumee. Titre foncier securise.',
                'prix'             => 18000000,
                'surface_m2'       => 600,
                'nb_pieces'        => 0,
                'nb_chambres'      => 0,
                'nb_salles_bain'   => 0,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'terrain',
                'ville'            => 'Douala',
                'quartier'         => 'Kotto',
                'adresse'          => 'Avenue de Kotto Douala',
                'latitude'         => 4.0760,
                'longitude'        => 9.7590,
                'vues'             => 198,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Appartement premium Cite des Palmiers',
                'description'      => 'Appartement premium dans la Cite des Palmiers. 3 chambres, salon de standing, cuisine haut de gamme, 2 salles de bain avec baignoire, terrasse. Residence luxueuse avec piscine et concierge.',
                'prix'             => 450000,
                'surface_m2'       => 130,
                'nb_pieces'        => 5,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Cite des Palmiers',
                'adresse'          => 'Avenue des Palmiers Douala',
                'latitude'         => 4.0450,
                'longitude'        => 9.7350,
                'vues'             => 378,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Appartement 2 chambres Ndokoti',
                'description'      => 'Appartement 2 chambres a Ndokoti, quartier commercial anime. Salon, cuisine, salle de bain, acces facilite aux transports. Proximite du marche Ndokoti.',
                'prix'             => 85000,
                'surface_m2'       => 55,
                'nb_pieces'        => 3,
                'nb_chambres'      => 2,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Douala',
                'quartier'         => 'Ndokoti',
                'adresse'          => 'Carrefour Ndokoti Douala',
                'latitude'         => 4.0650,
                'longitude'        => 9.7450,
                'vues'             => 112,
                'nb_photos'        => 2,
            ],
        ];
    }

    // ============================================
    // ANNONCES YAOUNDE
    // ============================================
    private function annoncesYaounde(): array
    {
        return [
            [
                'titre'            => 'Appartement meuble Bastos ambassades',
                'description'      => 'Appartement luxueux dans le quartier diplomatique de Bastos. 3 chambres, salon, cuisine equipee, 2 salles de bain, terrasse. Residence avec piscine et gardien 24h.',
                'prix'             => 500000,
                'surface_m2'       => 130,
                'nb_pieces'        => 5,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'appartement',
                'ville'            => 'Yaounde',
                'quartier'         => 'Bastos',
                'adresse'          => 'Rue 1234 Bastos Yaounde',
                'latitude'         => 3.8800,
                'longitude'        => 11.5200,
                'vues'             => 445,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Studio Ngoa-Ekelle proche universites',
                'description'      => 'Studio propre et abordable a Ngoa-Ekelle, a 5 minutes des facultes. Meuble simplement, eau et electricite inclus, wifi disponible. Ideal pour etudiant.',
                'prix'             => 40000,
                'surface_m2'       => 16,
                'nb_pieces'        => 1,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'studio',
                'ville'            => 'Yaounde',
                'quartier'         => 'Ngoa-Ekelle',
                'adresse'          => 'Quartier Ngoa-Ekelle Yaounde',
                'latitude'         => 3.8650,
                'longitude'        => 11.5000,
                'vues'             => 312,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Villa 4 chambres Biyem-Assi piscine',
                'description'      => 'Grande villa a Biyem-Assi. 4 chambres dont suite parentale, double salon, cuisine equipee, 2 salles de bain, grand jardin, piscine privee, parking 3 voitures, gardien.',
                'prix'             => 480000,
                'surface_m2'       => 210,
                'nb_pieces'        => 8,
                'nb_chambres'      => 4,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'villa',
                'ville'            => 'Yaounde',
                'quartier'         => 'Biyem-Assi',
                'adresse'          => 'Carrefour Biyem-Assi Yaounde',
                'latitude'         => 3.8300,
                'longitude'        => 11.4800,
                'vues'             => 378,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Appartement Essos residence calme',
                'description'      => 'Appartement dans une residence calme a Essos. 3 chambres, salon double, cuisine equipee, 2 salles de bain, balcon. Piscine, salle de sport, gardien 24h.',
                'prix'             => 320000,
                'surface_m2'       => 110,
                'nb_pieces'        => 5,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'appartement',
                'ville'            => 'Yaounde',
                'quartier'         => 'Essos',
                'adresse'          => 'Rue de la Paix Essos Yaounde',
                'latitude'         => 3.8850,
                'longitude'        => 11.5300,
                'vues'             => 201,
                'nb_photos'        => 4,
            ],
            [
                'titre'            => 'Chambre etudiant Melen universite',
                'description'      => 'Chambre meublee pour etudiant a Melen, a 10 minutes de l\'Universite de Yaounde II. Eau, electricite et wifi inclus. Cuisine partagee disponible.',
                'prix'             => 35000,
                'surface_m2'       => 14,
                'nb_pieces'        => 1,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'chambre',
                'ville'            => 'Yaounde',
                'quartier'         => 'Melen',
                'adresse'          => 'Quartier Melen Yaounde',
                'latitude'         => 3.8900,
                'longitude'        => 11.5400,
                'vues'             => 289,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Bureau centre Nlongkak Yaounde',
                'description'      => 'Bureau fonctionnel de 50m2 au centre de Nlongkak. Climatise, internet, open space ou bureau ferme. Parking, gardien. Adresse professionnelle.',
                'prix'             => 150000,
                'surface_m2'       => 50,
                'nb_pieces'        => 3,
                'nb_chambres'      => 0,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'bureau',
                'ville'            => 'Yaounde',
                'quartier'         => 'Nlongkak',
                'adresse'          => 'Avenue Kennedy Nlongkak Yaounde',
                'latitude'         => 3.8750,
                'longitude'        => 11.5100,
                'vues'             => 134,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Villa a vendre Omnisports Yaounde',
                'description'      => 'Villa a vendre pres du Palais des Sports. 4 chambres, 2 salles de bain, salon de reception, cuisine, garage, jardin. Construction 2022. Titre foncier en cours.',
                'prix'             => 65000000,
                'surface_m2'       => 200,
                'nb_pieces'        => 7,
                'nb_chambres'      => 4,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Yaounde',
                'quartier'         => 'Omnisports',
                'adresse'          => 'Avenue du 20 Mai Omnisports Yaounde',
                'latitude'         => 3.8540,
                'longitude'        => 11.5060,
                'vues'             => 456,
                'nb_photos'        => 5,
            ],
            [
                'titre'            => 'Appartement meuble Mvog-Mbi',
                'description'      => 'Appartement 2 chambres entierement meuble a Mvog-Mbi. Cuisine equipee, salon, salle de bain moderne. Eau, electricite et wifi inclus dans le loyer.',
                'prix'             => 140000,
                'surface_m2'       => 60,
                'nb_pieces'        => 3,
                'nb_chambres'      => 2,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'appartement',
                'ville'            => 'Yaounde',
                'quartier'         => 'Mvog-Mbi',
                'adresse'          => 'Rue de Mvog-Mbi Yaounde',
                'latitude'         => 3.8500,
                'longitude'        => 11.5150,
                'vues'             => 178,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Terrain 400m2 Emana Yaounde',
                'description'      => 'Terrain de 400m2 a batir a Emana. Viabilise eau et electricite, acces route bitumee. Titre foncier securise. Investissement sur.',
                'prix'             => 15000000,
                'surface_m2'       => 400,
                'nb_pieces'        => 0,
                'nb_chambres'      => 0,
                'nb_salles_bain'   => 0,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'terrain',
                'ville'            => 'Yaounde',
                'quartier'         => 'Emana',
                'adresse'          => 'Quartier Emana Yaounde',
                'latitude'         => 3.9200,
                'longitude'        => 11.5400,
                'vues'             => 167,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Studio neuf Omnisports Yaounde',
                'description'      => 'Studio neuf jamais habite pres du Palais des Sports. Parquet, cuisine equipee, balcon, gardien, parking. Quartier en plein developpement.',
                'prix'             => 95000,
                'surface_m2'       => 30,
                'nb_pieces'        => 1,
                'nb_chambres'      => 1,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => true,
                'categorie'        => 'studio',
                'ville'            => 'Yaounde',
                'quartier'         => 'Omnisports',
                'adresse'          => 'Rue Omnisports Yaounde',
                'latitude'         => 3.8540,
                'longitude'        => 11.5060,
                'vues'             => 223,
                'nb_photos'        => 3,
            ],
        ];
    }

    // ============================================
    // ANNONCES AUTRES VILLES
    // ============================================
    private function annoncesAutresVilles(): array
    {
        return [
            [
                'titre'            => 'Villa familiale Bafoussam centre',
                'description'      => 'Belle villa au centre de Bafoussam. 4 chambres, salon, salle a manger, cuisine, 2 salles de bain, jardin, parking. Quartier calme et residentiel.',
                'prix'             => 200000,
                'surface_m2'       => 160,
                'nb_pieces'        => 7,
                'nb_chambres'      => 4,
                'nb_salles_bain'   => 2,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Bafoussam',
                'quartier'         => 'Centre',
                'adresse'          => 'Avenue des Palmiers Bafoussam',
                'latitude'         => 5.4737,
                'longitude'        => 10.4176,
                'vues'             => 134,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Appartement 2 chambres Bafoussam',
                'description'      => 'Appartement 2 chambres en bon etat a Bafoussam. Salon, cuisine, salle de bain. Eau et electricite disponibles.',
                'prix'             => 70000,
                'surface_m2'       => 55,
                'nb_pieces'        => 3,
                'nb_chambres'      => 2,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Bafoussam',
                'quartier'         => 'Famla',
                'adresse'          => 'Quartier Famla Bafoussam',
                'latitude'         => 5.4720,
                'longitude'        => 10.4160,
                'vues'             => 89,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Villa a vendre Garoua titre foncier',
                'description'      => 'Villa a vendre a Garoua. 3 chambres, salon, cuisine, salle de bain, jardin clos. Titre foncier securise. Quartier residentiel tranquille.',
                'prix'             => 25000000,
                'surface_m2'       => 130,
                'nb_pieces'        => 5,
                'nb_chambres'      => 3,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'villa',
                'ville'            => 'Garoua',
                'quartier'         => 'Centre',
                'adresse'          => 'Rue principale Garoua',
                'latitude'         => 9.3000,
                'longitude'        => 13.3833,
                'vues'             => 201,
                'nb_photos'        => 3,
            ],
            [
                'titre'            => 'Appartement Bamenda quartier calme',
                'description'      => 'Appartement propre a Bamenda. 2 chambres, salon, cuisine, salle de bain. Proche du centre ville. Eau et electricite disponibles.',
                'prix'             => 60000,
                'surface_m2'       => 50,
                'nb_pieces'        => 3,
                'nb_chambres'      => 2,
                'nb_salles_bain'   => 1,
                'type_transaction' => 'location',
                'meuble'           => false,
                'categorie'        => 'appartement',
                'ville'            => 'Bamenda',
                'quartier'         => 'Centre',
                'adresse'          => 'Main Street Bamenda',
                'latitude'         => 5.9597,
                'longitude'        => 10.1456,
                'vues'             => 78,
                'nb_photos'        => 2,
            ],
            [
                'titre'            => 'Terrain 500m2 Bafoussam vue montagne',
                'description'      => 'Terrain avec vue sur les montagnes. 500m2, viabilise, acces route, titre foncier en cours. Ideal pour construction villa.',
                'prix'             => 8000000,
                'surface_m2'       => 500,
                'nb_pieces'        => 0,
                'nb_chambres'      => 0,
                'nb_salles_bain'   => 0,
                'type_transaction' => 'vente',
                'meuble'           => false,
                'categorie'        => 'terrain',
                'ville'            => 'Bafoussam',
                'quartier'         => 'Kamkop',
                'adresse'          => 'Quartier Kamkop Bafoussam',
                'latitude'         => 5.4800,
                'longitude'        => 10.4200,
                'vues'             => 156,
                'nb_photos'        => 2,
            ],
        ];
    }
}