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
            $table->string('start_date_bs', 20)->nullable()->after('start_date');
        });

        Schema::table('scheme_periods', function (Blueprint $table): void {
            $table->string('due_date_bs', 20)->nullable()->after('due_date');
        });

        Schema::create('scheme_period_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('savings_scheme_id')->constrained('savings_schemes')->cascadeOnDelete();
            $table->foreignId('scheme_period_id')->constrained('scheme_periods')->cascadeOnDelete();
            $table->foreignId('scheme_seat_id')->nullable()->constrained('scheme_seats')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['savings_scheme_id', 'scheme_period_id'], 'spr_scheme_period_idx');
            $table->index(['scheme_period_id', 'status'], 'spr_period_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheme_period_reservations');

        Schema::table('scheme_periods', function (Blueprint $table): void {
            $table->dropColumn('due_date_bs');
        });

        Schema::table('savings_schemes', function (Blueprint $table): void {
            $table->dropColumn('start_date_bs');
        });
    }
};
