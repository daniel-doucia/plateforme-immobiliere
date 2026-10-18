<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('annonce_id')->constrained()->restrictOnDelete();
            $table->foreignId('forfait_id')->constrained()->restrictOnDelete();
            $table->string('reference')->unique();

            // Copie du forfait au moment de la commande : un changement de tarif ne modifie pas l'historique
            $table->string('libelle');
            $table->unsignedSmallInteger('duree_jours');
            $table->unsignedBigInteger('montant');

            $table->string('statut', 20)->default('en_attente');
            $table->timestamp('payee_le')->nullable();
            $table->timestamps();

            $table->index(['annonce_id', 'statut']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
