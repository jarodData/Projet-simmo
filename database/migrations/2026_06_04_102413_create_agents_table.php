<?php
// database/migrations/2026_06_04_000001_add_cni_fields_to_agents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Créer la table agents si elle n'existe pas encore ──
        if (!Schema::hasTable('agents')) {
            Schema::create('agents', function (Blueprint $table) {
                $table->id();
                $table->string('prenom', 100);
                $table->string('nom', 100);
                $table->string('email', 200)->unique();
                $table->string('telephone', 20)->nullable();
                $table->string('mot_de_passe');
                $table->enum('statut', ['en_attente', 'actif', 'rejete', 'suspendu'])
                      ->default('en_attente');
                $table->timestamps();
            });
        }

        // ── Ajouter les champs CNI ──
        Schema::table('agents', function (Blueprint $table) {

            // Fichier uploadé
            $table->string('cni_chemin', 500)->nullable()
                  ->comment('Chemin du fichier CNI uploadé');

            // Résultat IA
            $table->boolean('cni_valide')->nullable()->default(null);
            $table->float('cni_score_confiance')->nullable();
            $table->enum('cni_recommandation', [
                'APPROUVER', 'REJETER', 'VERIFIER_MANUELLEMENT'
            ])->nullable();
            $table->text('cni_motif_rejet')->nullable();
            $table->json('cni_donnees')->nullable()
                  ->comment('Données extraites : nom, numéro, dates…');
            $table->json('cni_anomalies')->nullable();
            $table->timestamp('cni_analyse_at')->nullable();

            // Décision manuelle admin
            $table->boolean('cni_verif_manuelle')->default(false);
            $table->enum('cni_decision_admin', ['approuver', 'rejeter'])->nullable();
            $table->text('cni_motif_admin')->nullable();
            $table->unsignedBigInteger('cni_valide_par')->nullable()
                  ->comment('ID admin ayant pris la décision manuelle');
            $table->timestamp('cni_decision_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'cni_chemin', 'cni_valide', 'cni_score_confiance',
                'cni_recommandation', 'cni_motif_rejet', 'cni_donnees',
                'cni_anomalies', 'cni_analyse_at', 'cni_verif_manuelle',
                'cni_decision_admin', 'cni_motif_admin',
                'cni_valide_par', 'cni_decision_at',
            ]);
        });
    }
};
