<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forfaits', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type_bien', 20)->nullable(); // null = valable pour tous les biens
            $table->unsignedSmallInteger('duree_jours');
            $table->unsignedBigInteger('prix'); // FCFA
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['actif', 'type_bien']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forfaits');
    }
};
