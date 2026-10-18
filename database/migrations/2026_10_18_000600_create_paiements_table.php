<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->restrictOnDelete();
            $table->string('fournisseur', 30);
            $table->string('reference_fournisseur')->nullable();
            $table->unsignedBigInteger('montant');
            $table->string('statut', 20);
            $table->json('payload')->nullable();
            $table->timestamp('paye_le')->nullable();
            $table->timestamps();

            $table->unique(['fournisseur', 'reference_fournisseur']);
            $table->index('commande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
