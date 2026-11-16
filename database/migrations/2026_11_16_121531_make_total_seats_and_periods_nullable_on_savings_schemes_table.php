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
        Schema::table('savings_schemes', function (Blueprint $table): void {
            $table->unsignedInteger('total_seats')->default(1)->nullable()->change();
            $table->unsignedInteger('total_periods')->default(1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_schemes', function (Blueprint $table): void {
            $table->unsignedInteger('total_seats')->default(20)->nullable(false)->change();
            $table->unsignedInteger('total_periods')->default(20)->nullable(false)->change();
        });
    }
};
