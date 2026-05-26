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
        Schema::create('messagess', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('id_conversation');

            // qui envoie
            $table->string('sender_type');

            // id utilisateur/agent/admin
            $table->unsignedBigInteger('sender_id');

            // contenu message
            $table->text('contenu');

            // message vu ?
            $table->boolean('lu')->default(false);

            $table->timestamps();

            // relation
            $table->foreign('id_conversation')
                ->references('id')
                ->on('conversations')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messagess');
    }
};