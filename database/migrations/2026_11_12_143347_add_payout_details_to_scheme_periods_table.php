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
        Schema::table('scheme_periods', function (Blueprint $table): void {
            $table->string('payout_method', 50)->nullable()->after('payout_status'); // Cash, eSewa, Khalti, Bank Transfer
            $table->string('payout_reference', 100)->nullable()->after('payout_method');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('net_payout_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_periods', function (Blueprint $table): void {
            $table->dropColumn(['payout_method', 'payout_reference', 'paid_amount']);
        });
    }
};
