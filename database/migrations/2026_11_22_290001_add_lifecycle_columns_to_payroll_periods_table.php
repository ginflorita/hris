<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for the rest of blueprint §15's state machine (Phase 11
     * only ever wrote processed_at/processed_by, for Draft->ForReview).
     */
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->timestamp('submitted_for_approval_at')->nullable()->after('processed_by');
            $table->foreignId('submitted_by')->nullable()->after('submitted_for_approval_at')->constrained('users')->nullOnDelete();

            $table->timestamp('approved_at')->nullable()->after('submitted_by');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable()->after('approved_by');

            $table->timestamp('finalized_at')->nullable()->after('rejection_reason');
            $table->foreignId('finalized_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();

            $table->timestamp('locked_at')->nullable()->after('finalized_by');
            $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();

            $table->timestamp('published_at')->nullable()->after('locked_by');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn([
                'submitted_for_approval_at', 'approved_at', 'rejection_reason',
                'finalized_at', 'locked_at', 'published_at',
            ]);
        });
    }
};
