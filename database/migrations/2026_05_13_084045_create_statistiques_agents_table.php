<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statistiques_agents', function (Blueprint $table) {
             $table->id();
        $table->foreignId('id_agent')->constrained('agents_immobiliers')->cascadeOnDelete();
        $table->integer('mois');
        $table->integer('annee');
        $table->integer('nb_annonces_publiees')->default(0);
        $table->integer('nb_contacts_recus')->default(0);
        $table->integer('nb_services_rendus')->default(0);
        $table->integer('nb_vues_total')->default(0);
        $table->unique(['id_agent', 'mois', 'annee']);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistiques_agents');
    }
};
