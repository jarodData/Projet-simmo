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
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
        $table->string('nom', 100);
        $table->string('prenom', 100);
        $table->string('email', 150)->unique();
        $table->string('mot_de_passe_hash');
        $table->string('telephone', 20)->nullable();
        $table->enum('type_user', ['etudiant', 'famille', 'professionnel'])->default('etudiant');
        $table->string('token_verification')->nullable();
        $table->boolean('is_verified')->default(false);
        $table->string('avatar')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
