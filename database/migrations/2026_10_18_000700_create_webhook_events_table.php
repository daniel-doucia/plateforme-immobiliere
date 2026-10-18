<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('fournisseur', 30);
            $table->string('event_id');
            $table->json('payload')->nullable();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();

            $table->unique(['fournisseur', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
