<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages_contact', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120);
            $table->string('email', 190);
            $table->string('telephone', 30)->nullable();
            $table->string('sujet', 150);
            $table->text('message');
            $table->timestamp('lu_le')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages_contact');
    }
};
