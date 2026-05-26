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

            // utilisateur ou admin
            $table->unsignedBigInteger('id_utilisateur')->nullable();

            // agent immobilier
            $table->unsignedBigInteger('id_agent')->nullable();

            // dernier message
            $table->text('dernier_message')->nullable();

            // date dernier message
            $table->timestamp('dernier_message_at')->nullable();

            $table->timestamps();

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