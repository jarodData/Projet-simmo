<?php
// app/Models/Agent.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class Agent extends Authenticatable
{
    protected $table = 'agents';

    protected $fillable = [
        'prenom', 'nom', 'email', 'telephone', 'mot_de_passe', 'statut',
        'cni_chemin', 'cni_valide', 'cni_score_confiance', 'cni_recommandation',
        'cni_motif_rejet', 'cni_donnees', 'cni_anomalies', 'cni_analyse_at',
        'cni_verif_manuelle', 'cni_decision_admin', 'cni_motif_admin',
        'cni_valide_par', 'cni_decision_at',
    ];

    protected $hidden = ['mot_de_passe'];

    protected $casts = [
        'cni_valide'         => 'boolean',
        'cni_verif_manuelle' => 'boolean',
        'cni_donnees'        => 'array',   // JSON → array auto
        'cni_anomalies'      => 'array',   // JSON → array auto
        'cni_analyse_at'     => 'datetime',
        'cni_decision_at'    => 'datetime',
        'cni_score_confiance'=> 'float',
    ];

    // ────────────────────────────────────────────────
    //  Accesseurs
    // ────────────────────────────────────────────────

    /** Nom complet */
    protected function nomComplet(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->prenom} {$this->nom}"
        );
    }

    /** Score en % pour l'affichage */
    protected function scorePourcentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cni_score_confiance
                ? (int) round($this->cni_score_confiance * 100)
                : null
        );
    }

    /** Label statut CNI lisible */
    protected function cniStatutLabel(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->cni_decision_admin) {
                $action = $this->cni_decision_admin === 'approuver' ? 'Approuvée' : 'Rejetée';
                return "{$action} par admin";
            }
            return match ($this->cni_recommandation) {
                'APPROUVER'              => 'Approuvée par IA',
                'REJETER'               => 'Rejetée par IA',
                'VERIFIER_MANUELLEMENT' => 'Vérification manuelle requise',
                default                  => 'Non analysée',
            };
        });
    }

    /** Couleur Bootstrap selon recommandation */
    protected function cniCouleur(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->cni_recommandation) {
            'APPROUVER'              => 'success',
            'REJETER'               => 'danger',
            'VERIFIER_MANUELLEMENT' => 'warning',
            default                  => 'secondary',
        });
    }

    /** Jours avant expiration CNI */
    protected function joursAvantExpiration(): Attribute
    {
        return Attribute::make(get: function () {
            $donnees = $this->cni_donnees ?? [];
            $exp = $donnees['date_expiration'] ?? null;
            if (!$exp) return null;
            try {
                return (int) Carbon::today()->diffInDays(Carbon::parse($exp), false);
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    // ────────────────────────────────────────────────
    //  Scopes
    // ────────────────────────────────────────────────

    public function scopeEnAttenteCni($query)
    {
        return $query->whereNotNull('cni_chemin')
                     ->whereNull('cni_recommandation');
    }

    public function scopeCniApprouvees($query)
    {
        return $query->where('cni_valide', true);
    }

    public function scopeCniRejetees($query)
    {
        return $query->where('cni_valide', false)
                     ->whereNotNull('cni_recommandation');
    }

    public function scopeVerifManuelle($query)
    {
        return $query->where('cni_verif_manuelle', true);
    }

    // ────────────────────────────────────────────────
    //  Méthodes métier
    // ────────────────────────────────────────────────

    /**
     * Applique le résultat du VerificateurCni sur l'agent.
     * Appeler après CniVerificateur::analyser().
     */
    public function appliquerResultatIa(array $resultat): void
    {
        if (! ($resultat['succes'] ?? false)) {
            return;
        }

        $analyse = $resultat['analyse'];
        $reco    = $analyse['recommandation'] ?? 'VERIFIER_MANUELLEMENT';

        $this->cni_score_confiance = $analyse['score_confiance']       ?? null;
        $this->cni_recommandation  = $reco;
        $this->cni_motif_rejet     = $analyse['motif_recommandation']  ?? null;
        $this->cni_donnees         = $analyse['donnees_extraites']     ?? [];
        $this->cni_anomalies       = $analyse['anomalies']             ?? [];
        $this->cni_analyse_at      = now();

        match ($reco) {
            'APPROUVER' => $this->forceFill(['statut' => 'actif',   'cni_valide' => true]),
            'REJETER'   => $this->forceFill(['statut' => 'rejete',  'cni_valide' => false]),
            default     => $this->forceFill(['cni_verif_manuelle' => true]),
        };

        $this->save();
    }

    /**
     * Décision manuelle d'un admin.
     */
    public function decisionAdmin(string $action, string $motif = '', int $adminId = null): void
    {
        $this->cni_decision_admin  = $action;
        $this->cni_motif_admin     = $motif;
        $this->cni_verif_manuelle  = false;
        $this->cni_valide_par      = $adminId;
        $this->cni_decision_at     = now();

        if ($action === 'approuver') {
            $this->statut     = 'actif';
            $this->cni_valide = true;
        } else {
            $this->statut     = 'rejete';
            $this->cni_valide = false;
        }

        $this->save();
    }

    // ────────────────────────────────────────────────
    //  Relations
    // ────────────────────────────────────────────────

    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'id_agent');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'cni_valide_par');
    }
}
