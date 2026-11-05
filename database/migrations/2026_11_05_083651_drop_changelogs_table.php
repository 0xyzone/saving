<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('changelogs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('type')->default('macro');
            $table->string('badge')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('highlights')->nullable();
            $table->date('released_at')->nullable();
            $table->timestamps();
        });
    }
};
