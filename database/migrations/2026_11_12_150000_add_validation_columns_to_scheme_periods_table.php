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
            $table->boolean('is_validated')->default(false)->after('status');
            $table->timestamp('validated_at')->nullable()->after('is_validated');
            $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            $table->text('validation_notes')->nullable()->after('validated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_periods', function (Blueprint $table): void {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['is_validated', 'validated_at', 'validated_by', 'validation_notes']);
        });
    }
};
