<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes_recherche', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('q', 100)->nullable();
            $table->string('type_offre', 20)->nullable();
            $table->string('type_bien', 20)->nullable();
            // En cascade : si le lieu disparaît, l'alerte disparaît (jamais d'alerte élargie par erreur)
            $table->foreignId('ville_id')->nullable()->constrained('villes')->cascadeOnDelete();
            $table->foreignId('quartier_id')->nullable()->constrained('quartiers')->cascadeOnDelete();
            $table->unsignedBigInteger('prix_min')->nullable();
            $table->unsignedBigInteger('prix_max')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('derniere_verification_le')->nullable();
            $table->timestamps();

            $table->index(['actif', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes_recherche');
    }
};
