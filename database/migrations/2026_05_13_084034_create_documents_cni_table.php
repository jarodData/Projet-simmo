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
        Schema::create('documents_cni', function (Blueprint $table) {
$table->id();
        $table->foreignId('id_agent')->constrained('agents_immobiliers')->cascadeOnDelete();
        $table->string('chemin_fichier');
        $table->enum('statut_verification', ['en_attente', 'valide', 'rejete'])->default('en_attente');
        $table->text('commentaire_admin')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_cni');
    }
};
