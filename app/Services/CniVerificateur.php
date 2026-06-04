<?php
// app/Services/CniVerificateur.php
// Service IA — appelle Claude Vision pour analyser la CNI

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CniVerificateur
{
    private string $apiKey;
    private string $model   = 'claude-sonnet-4-20250514';
    private int    $maxTokens = 1024;

    // Taille max : 5 Mo
    private int $tailleMaxOctets = 5 * 1024 * 1024;

    private array $mimesAcceptes = [
        'image/jpeg' => 'image/jpeg',
        'image/jpg'  => 'image/jpeg',
        'image/png'  => 'image/png',
        'image/webp' => 'image/webp',
        'image/gif'  => 'image/gif',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key')
            ?? env('ANTHROPIC_API_KEY')
            ?? throw new \RuntimeException('ANTHROPIC_API_KEY manquante');
    }

    // ────────────────────────────────────────────────
    //  Point d'entrée principal
    // ────────────────────────────────────────────────

    /**
     * Analyse une CNI.
     *
     * @param  UploadedFile|string  $source  Fichier uploadé OU chemin absolu
     * @return array  ['succes' => bool, 'analyse' => array|null, 'erreur' => string|null]
     */
    public function analyser(UploadedFile|string $source): array
    {
        try {
            [$imageBase64, $mimeType] = $this->chargerImage($source);
        } catch (\InvalidArgumentException $e) {
            return $this->erreur($e->getMessage());
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'system'     => $this->promptSysteme(),
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mimeType,
                                'data'       => $imageBase64,
                            ],
                        ],
                        ['type' => 'text', 'text' => $this->promptAnalyse()],
                    ],
                ]],
            ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Erreur API Anthropic : ' . $response->status() . ' — ' . $response->body()
                );
            }

            $texte    = $response->json('content.0.text', '');
            $texte    = $this->nettoyerJson($texte);
            $resultat = json_decode($texte, true, 512, JSON_THROW_ON_ERROR);
            $resultat = $this->enrichir($resultat);

            Log::info('[CNI] Analyse OK', [
                'recommandation' => $resultat['recommandation'],
                'score'          => $resultat['score_confiance'],
            ]);

            return ['succes' => true, 'analyse' => $resultat];

        } catch (\JsonException $e) {
            Log::error('[CNI] JSON invalide', ['error' => $e->getMessage()]);
            return $this->erreur("Réponse IA non parseable : {$e->getMessage()}");
        } catch (\Exception $e) {
            Log::error('[CNI] Erreur API', ['error' => $e->getMessage()]);
            return $this->erreur("Erreur API : {$e->getMessage()}");
        }
    }

    // ────────────────────────────────────────────────
    //  Chargement image
    // ────────────────────────────────────────────────

    private function chargerImage(UploadedFile|string $source): array
    {
        if ($source instanceof UploadedFile) {
            $mime = $source->getMimeType();
            if (!isset($this->mimesAcceptes[$mime])) {
                throw new \InvalidArgumentException(
                    "Format non supporté : {$mime}. Acceptés : jpeg, png, webp"
                );
            }
            if ($source->getSize() > $this->tailleMaxOctets) {
                throw new \InvalidArgumentException('Image trop lourde (max 5 Mo)');
            }
            $contenu = file_get_contents($source->getRealPath());
            return [base64_encode($contenu), $this->mimesAcceptes[$mime]];
        }

        // Chemin fichier
        if (!file_exists($source)) {
            throw new \InvalidArgumentException("Fichier introuvable : {$source}");
        }
        $ext  = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        if (!isset($mimes[$ext])) {
            throw new \InvalidArgumentException("Extension non supportée : {$ext}");
        }
        if (filesize($source) > $this->tailleMaxOctets) {
            throw new \InvalidArgumentException('Image trop lourde (max 5 Mo)');
        }
        return [base64_encode(file_get_contents($source)), $mimes[$ext]];
    }

    // ────────────────────────────────────────────────
    //  Enrichissement post-IA
    // ────────────────────────────────────────────────

    private function enrichir(array $r): array
    {
        $donnees = &$r['donnees_extraites'];
        $verif   = &$r['verification'];

        // Âge
        if (!empty($donnees['date_naissance'])) {
            try {
                $naissance      = new \DateTime($donnees['date_naissance']);
                $age            = $naissance->diff(new \DateTime())->y;
                $donnees['age'] = $age;
                if ($age < 18) {
                    $r['anomalies'][]     = "Titulaire mineur ({$age} ans)";
                    $r['recommandation']  = 'REJETER';
                }
            } catch (\Exception) {
                $donnees['age'] = null;
            }
        }

        // Expiration
        if (!empty($donnees['date_expiration'])) {
            try {
                $exp   = new \DateTime($donnees['date_expiration']);
                $today = new \DateTime();
                $delta = (int) $today->diff($exp)->format('%r%a'); // négatif si expiré
                $donnees['jours_avant_expiration'] = $delta;
                if ($delta < 0) {
                    $r['anomalies'][]    = 'CNI expirée depuis ' . abs($delta) . ' jours';
                    $verif['non_expire'] = false;
                } elseif ($delta < 30) {
                    $r['anomalies'][] = "CNI expire dans {$delta} jours";
                }
            } catch (\Exception) {
                $donnees['jours_avant_expiration'] = null;
            }
        }

        // Score lisibilité
        $checksOk = count(array_filter($verif, fn($v) => $v === true));
        $total    = count($verif) ?: 1;
        $r['score_lisibilite'] = round($checksOk / $total, 2);

        // Couleur statut
        $r['statut_couleur'] = match ($r['recommandation'] ?? '') {
            'APPROUVER'              => 'success',
            'REJETER'               => 'danger',
            'VERIFIER_MANUELLEMENT' => 'warning',
            default                  => 'secondary',
        };

        $r['analyse_at'] = now()->toISOString();
        return $r;
    }

    // ────────────────────────────────────────────────
    //  Prompts
    // ────────────────────────────────────────────────

    private function promptSysteme(): string
    {
        return <<<PROMPT
Tu es un expert en vérification de documents d'identité camerounais.
Tu analyses des Cartes Nationales d'Identité (CNI) du Cameroun.

Une CNI camerounaise valide contient :
- Recto : photo du titulaire, nom, prénom(s), date de naissance, lieu de naissance,
  numéro CNI (format : XXXXXXXXXX), date d'émission, date d'expiration,
  la mention "REPUBLIQUE DU CAMEROUN / REPUBLIC OF CAMEROON",
  autorité émettrice (DGSN), signature du titulaire.
- Verso : adresse, profession, taille, groupe sanguin, empreinte digitale.

Réponds UNIQUEMENT en JSON valide, sans texte autour, sans balises markdown.
PROMPT;
    }

    private function promptAnalyse(): string
    {
        return <<<PROMPT
Analyse cette image de CNI camerounaise et retourne un JSON avec exactement cette structure :

{
  "valide": true,
  "score_confiance": 0.95,
  "donnees_extraites": {
    "nom": "",
    "prenoms": "",
    "date_naissance": "YYYY-MM-DD",
    "lieu_naissance": "",
    "numero_cni": "",
    "date_emission": "YYYY-MM-DD",
    "date_expiration": "YYYY-MM-DD",
    "sexe": "M",
    "nationalite": "Camerounaise",
    "face_detectee": true
  },
  "verification": {
    "document_authentique": true,
    "photo_presente": true,
    "texte_lisible": true,
    "republique_cameroun_mentionnee": true,
    "numero_format_valide": true,
    "non_expire": true,
    "coherence_donnees": true
  },
  "anomalies": [],
  "recommandation": "APPROUVER",
  "motif_recommandation": ""
}

Mets null pour les champs non lisibles. Remplis anomalies[] si : document expiré,
photo absente, texte flou, falsification suspectée, document étranger, image coupée.
PROMPT;
    }

    // ────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────

    private function nettoyerJson(string $texte): string
    {
        $texte = trim($texte);
        $texte = preg_replace('/^```json\s*/i', '', $texte);
        $texte = preg_replace('/\s*```$/', '', $texte);
        return $texte;
    }

    private function erreur(string $message): array
    {
        return ['succes' => false, 'analyse' => null, 'erreur' => $message];
    }
}
