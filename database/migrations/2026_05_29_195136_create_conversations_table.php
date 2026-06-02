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
        Schema::create('conversations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('annonce_id')->constrained('annonces')->onDelete('cascade');
    $table->foreignId('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
    $table->foreignId('agent_id')->constrained('agents_immobiliers')->onDelete('cascade');
    $table->timestamp('dernier_message_at')->nullable();
    $table->timestamps();

    // Un seul fil par (annonce + client)
    $table->unique(['annonce_id', 'utilisateur_id']);
});
 Schema::table('conversations', function (Blueprint $table) {
        $table->renameColumn('utilisateur_id', 'client_id');
    });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
