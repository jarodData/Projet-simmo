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
        Schema::create('recommandations', function (Blueprint $table) {
            $table->id();
        $table->foreignId('id_user')->constrained('utilisateurs')->cascadeOnDelete();
        $table->foreignId('id_annonce')->constrained('annonces')->cascadeOnDelete();
        $table->float('score_recommandation');
        $table->text('raison')->nullable();
        $table->boolean('est_vue')->default(false);
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommandations');
    }
};
