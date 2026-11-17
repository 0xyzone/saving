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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('primary_contact_number')->nullable()->change();
        });

        Schema::table('savings_schemes', function (Blueprint $table) {
            $table->decimal('khaja_kharcha', 12, 2)->default(2000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_schemes', function (Blueprint $table) {
            $table->decimal('khaja_kharcha', 12, 2)->default(0)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('primary_contact_number')->nullable(false)->change();
        });
    }
};
