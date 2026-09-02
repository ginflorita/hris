<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-review, manager review, and peer review (blueprint §22) are one
     * table differentiated by `type`, not three near-identical tables --
     * same reasoning as CompensationItem/PerformanceGoal. `reviewer_id`
     * always points at an Employee (for a self-review it equals
     * employee_id); "Ratings" and "Comments" are just columns on the same
     * row rather than their own tables, the same collapse Interview made
     * for its rating/recommendation/feedback columns. "Performance
     * history" isn't a table either -- it's every review+goal row for an
     * employee across cycles, queried, not duplicated.
     */
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
