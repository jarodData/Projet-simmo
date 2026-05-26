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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
        $table->string('nom_plan', 100);
        $table->decimal('prix_mensuel', 10, 2)->default(0);
        $table->integer('duree_essai_jours')->default(30);
        $table->integer('nb_annonces_max')->default(10);
        $table->json('fonctionnalites')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
