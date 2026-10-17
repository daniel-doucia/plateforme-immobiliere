<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quartiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ville_id')->constrained()->restrictOnDelete();
            $table->string('nom');
            $table->string('slug');
            $table->unsignedTinyInteger('arrondissement')->nullable();
            $table->timestamps();

            $table->unique(['ville_id', 'slug']);
            $table->index(['ville_id', 'arrondissement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quartiers');
    }
};
