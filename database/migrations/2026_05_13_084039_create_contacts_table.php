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
        Schema::create('contacts', function (Blueprint $table) {
             $table->id();
        $table->foreignId('id_user')->constrained('utilisateurs')->cascadeOnDelete();
        $table->foreignId('id_agent')->constrained('agents_immobiliers')->cascadeOnDelete();
        $table->foreignId('id_annonce')->constrained('annonces')->cascadeOnDelete();
        $table->text('message')->nullable();
        $table->enum('statut', ['lu', 'non_lu'])->default('non_lu');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
