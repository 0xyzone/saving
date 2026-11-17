<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scheme_installments', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_due');
        });

        DB::table('scheme_installments')
            ->whereIn('status', ['paid', 'auto_settled_winner'])
            ->update(['paid_amount' => DB::raw('total_due')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_installments', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
