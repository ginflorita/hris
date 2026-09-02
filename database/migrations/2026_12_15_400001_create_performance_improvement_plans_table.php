<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A PIP is a forward-looking corrective process (reason, expected
     * goals, a bounded improvement period, a closing outcome), not a
     * backward-looking assessment like PerformanceReview -- different
     * enough in shape that it earns its own table rather than being a
     * PerformanceReview subtype. `review_id` is nullable: a PIP is
     * commonly triggered by a poor review, but not required to be (it
     * can also follow a standalone incident/conduct issue).
     */
    public function up(): void
    {
        Schema::create('performance_improvement_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_review_id')->nullable()->constrained('performance_reviews')->nullOnDelete();
            $table->foreignId('initiated_by')->constrained('employees')->cascadeOnDelete();
            $table->text('reason');
            $table->text('goals');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->text('outcome_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_improvement_plans');
    }
};
