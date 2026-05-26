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
        Schema::create('categories_bien', function (Blueprint $table) {
           $table->id();
        $table->enum('libelle', ['appartement', 'studio', 'villa', 'chambre', 'bureau', 'terrain']);
        $table->text('description')->nullable();
        $table->string('icone', 100)->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories_bien');
    }
};
