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
        Schema::create('photos_annonces', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_annonce')->constrained('annonces')->cascadeOnDelete();
        $table->string('chemin_image');
        $table->boolean('est_principale')->default(false);
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos_annonces');
    }
};
