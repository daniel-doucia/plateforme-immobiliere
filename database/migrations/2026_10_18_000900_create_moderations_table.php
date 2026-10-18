<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moderateur_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 20);
            $table->text('motif')->nullable();
            $table->timestamps();

            $table->index('annonce_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderations');
    }
};
