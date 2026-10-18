<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('quartier_id')->constrained('quartiers')->restrictOnDelete();

            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('description');

            $table->string('type_bien', 20);
            $table->string('type_offre', 20);
            $table->unsignedBigInteger('prix'); // en francs CFA, sans décimales
            $table->string('periodicite', 20)->nullable();
            $table->unsignedBigInteger('caution')->nullable();

            $table->unsignedInteger('superficie')->nullable(); // en m²
            $table->unsignedTinyInteger('nb_pieces')->nullable();
            $table->unsignedTinyInteger('nb_chambres')->nullable();
            $table->unsignedTinyInteger('nb_salles_bain')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('adresse_indicative')->nullable();

            $table->string('statut', 30)->default('brouillon');
            $table->text('motif_rejet')->nullable();
            $table->timestamp('publiee_le')->nullable();
            $table->timestamp('expire_le')->nullable();
            $table->unsignedInteger('vues_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'type_offre', 'type_bien', 'quartier_id', 'prix'], 'annonces_recherche_index');
            $table->index('expire_le');
            $table->index(['user_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
