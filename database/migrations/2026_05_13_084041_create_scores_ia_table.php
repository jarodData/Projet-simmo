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
        Schema::create('scores_ia', function (Blueprint $table) {
           $table->id();
        $table->foreignId('id_annonce')->constrained('annonces')->cascadeOnDelete();
        $table->float('score_nlp')->nullable();
        $table->decimal('prix_predit', 12, 2)->nullable();
        $table->float('score_pertinence')->nullable();
        $table->string('version_modele', 50)->nullable();
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores_ia');
    }
};
