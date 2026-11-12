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
        Schema::create('savings_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_seats')->default(20);
            $table->unsignedInteger('total_periods')->default(20);
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('khaja_kharcha', 12, 2)->default(0);
            $table->string('frequency')->default('monthly'); // daily, weekly, biweekly, monthly
            $table->date('start_date');
            $table->string('status')->default('draft'); // draft, open_for_applications, active, completed, cancelled
            $table->unsignedInteger('current_period')->default(1);
            $table->timestamps();
        });

        Schema::create('scheme_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_scheme_id')->constrained('savings_schemes')->cascadeOnDelete();
            $table->unsignedInteger('seat_number');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('available'); // available, pending_approval, assigned, won
            $table->unsignedInteger('won_at_period')->nullable();
            $table->timestamps();

            $table->unique(['savings_scheme_id', 'seat_number']);
        });

        Schema::create('scheme_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_scheme_id')->constrained('savings_schemes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('requested_seats_count')->default(1);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('scheme_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_scheme_id')->constrained('savings_schemes')->cascadeOnDelete();
            $table->unsignedInteger('period_number');
            $table->date('due_date');
            $table->foreignId('winning_seat_id')->nullable()->constrained('scheme_seats')->nullOnDelete();
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('gross_pool_amount', 12, 2)->default(0);
            $table->decimal('winner_dues_deducted', 12, 2)->default(0);
            $table->decimal('khaja_kharcha_deducted', 12, 2)->default(0);
            $table->decimal('net_payout_amount', 12, 2)->default(0);
            $table->string('payout_status')->default('pending'); // pending, paid
            $table->timestamp('paid_at')->nullable();
            $table->text('payout_notes')->nullable();
            $table->string('status')->default('upcoming'); // upcoming, in_progress, completed
            $table->timestamps();

            $table->unique(['savings_scheme_id', 'period_number']);
        });

        Schema::create('scheme_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_scheme_id')->constrained('savings_schemes')->cascadeOnDelete();
            $table->foreignId('scheme_period_id')->constrained('scheme_periods')->cascadeOnDelete();
            $table->foreignId('scheme_seat_id')->constrained('scheme_seats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('base_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2);
            $table->string('status')->default('pending'); // pending, submitted, paid, auto_settled_winner, overdue
            $table->foreignId('user_payment_option_id')->nullable()->constrained('user_payment_options')->nullOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('payment_proof_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheme_installments');
        Schema::dropIfExists('scheme_periods');
        Schema::dropIfExists('scheme_applications');
        Schema::dropIfExists('scheme_seats');
        Schema::dropIfExists('savings_schemes');
    }
};
