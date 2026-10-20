<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forfait_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forfait_id')->nullable()->constrained()->nullOnDelete();
            $table->string('forfait_nom'); // copie : l'historique reste lisible si le forfait est supprimé
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('avant')->nullable();
            $table->json('apres')->nullable();
            $table->timestamps();

            $table->index('forfait_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forfait_modifications');
    }
};
