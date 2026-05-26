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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_agent')->constrained('agents_immobiliers')->cascadeOnDelete();
        $table->foreignId('id_plan')->constrained('plans');
        $table->enum('operateur', ['orange_money', 'mtn_momo']);
        $table->string('telephone', 20);
        $table->decimal('montant', 10, 2);
        $table->string('reference')->unique();
        $table->enum('statut', ['en_attente', 'succes', 'echec', 'annule'])
              ->default('en_attente');
        $table->string('transaction_id')->nullable();
        $table->json('reponse_api')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
