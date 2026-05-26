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
        Schema::create('historique_recherches', function (Blueprint $table) {
$table->id();
        $table->foreignId('id_user')->constrained('utilisateurs')->cascadeOnDelete();
        $table->string('terme_recherche')->nullable();
        $table->json('filtres_appliques')->nullable();
        $table->integer('nb_resultats')->default(0);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_recherches');
    }
};
