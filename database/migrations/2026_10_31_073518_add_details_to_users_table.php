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
            $table->string('email')->nullable()->change();
            $table->string('primary_contact_number')->unique()->after('id');
            $table->string('secondary_contact_number')->nullable()->after('primary_contact_number');
            $table->text('home_address')->nullable()->after('secondary_contact_number');
            $table->string('avatar_url')->nullable()->after('home_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->dropColumn([
                'primary_contact_number',
                'secondary_contact_number',
                'home_address',
                'avatar_url',
            ]);
        });
    }
};
