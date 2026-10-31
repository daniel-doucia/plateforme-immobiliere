<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Vide = compte actif
            $table->timestamp('desactive_le')->nullable()->index();
            $table->string('motif_desactivation', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['desactive_le']);
            $table->dropColumn(['desactive_le', 'motif_desactivation']);
        });
    }
};
