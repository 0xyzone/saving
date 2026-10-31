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
        Schema::table('changelogs', function (Blueprint $table) {
            $table->string('badge')->nullable()->after('type'); // e.g. "Feature Release", "Security Fix"
            $table->json('highlights')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('changelogs', function (Blueprint $table) {
            $table->dropColumn(['badge', 'highlights']);
        });
    }
};
