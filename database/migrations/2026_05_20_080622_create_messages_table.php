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
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_utilisateur')
            ->nullable()
            ->constrained('utilisateurs')
            ->nullOnDelete();
        $table->foreignId('id_agent')
            ->constrained('agents_immobiliers')
            ->cascadeOnDelete();
        $table->foreignId('id_annonce')
            ->nullable()
            ->constrained('annonces')
            ->nullOnDelete();
        $table->enum('expediteur', ['utilisateur', 'agent']);
        $table->text('contenu');
        $table->boolean('lu')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
