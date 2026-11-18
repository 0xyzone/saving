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
        Schema::create('installment_settlement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_seat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_installment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->date('payment_date');
            $table->string('payment_date_bs')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_settlement_requests');
    }
};
