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
        Schema::create('favoris', function (Blueprint $table) {
            $table->id();
        $table->foreignId('id_utilisateur')
            ->constrained('utilisateurs')
            ->cascadeOnDelete();
        $table->foreignId('id_annonce')
            ->constrained('annonces')
            ->cascadeOnDelete();
        $table->unique(['id_utilisateur', 'id_annonce']);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};
