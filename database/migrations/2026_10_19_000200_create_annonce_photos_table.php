<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonce_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained()->cascadeOnDelete();
            $table->string('chemin');
            $table->string('mime', 20);
            $table->unsignedInteger('taille');
            $table->unsignedSmallInteger('largeur');
            $table->unsignedSmallInteger('hauteur');
            $table->unsignedTinyInteger('ordre')->default(1);
            $table->timestamps();

            $table->index(['annonce_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonce_photos');
    }
};
