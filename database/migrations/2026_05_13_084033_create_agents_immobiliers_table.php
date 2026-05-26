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
        Schema::create('agents_immobiliers', function (Blueprint $table) {
            $table->id();
        $table->string('nom', 100);
        $table->string('prenom', 100);
        $table->string('email', 150)->unique();
        $table->string('mot_de_passe_hash');
        $table->string('telephone', 20)->nullable();
        $table->string('numero_agence', 100)->nullable();
        $table->enum('statut', ['en_attente', 'actif', 'suspendu'])->default('en_attente');
        $table->string('token_verification')->nullable();
        $table->boolean('is_verified')->default(false);
        $table->string('avatar')->nullable();
        $table->foreignId('id_plan')->nullable()->constrained('plans')->nullOnDelete();
        $table->timestamp('date_souscription')->nullable();
        $table->timestamp('date_expiration_plan')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents_immobiliers');
    }
};
