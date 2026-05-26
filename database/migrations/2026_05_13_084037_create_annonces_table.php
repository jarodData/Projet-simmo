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
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
        $table->string('titre');
        $table->text('description');
        $table->decimal('prix', 12, 2);
        $table->float('surface_m2')->nullable();
        $table->integer('nb_pieces')->nullable();
        $table->integer('nb_chambres')->nullable();
        $table->integer('nb_salles_bain')->nullable();
        $table->enum('type_transaction', ['location', 'vente']);
        $table->enum('statut', ['active', 'inactive', 'vendu'])->default('active');
        $table->boolean('meuble')->default(false);
        $table->integer('vues')->default(0);
        $table->float('score_ia')->default(0);
        $table->foreignId('id_agent')->constrained('agents_immobiliers')->cascadeOnDelete();
        $table->foreignId('id_categorie')->constrained('categories_bien');
        $table->foreignId('id_localisation')->constrained('localisations');
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
