<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Effective-dated and append-only, the same shape as Employment/
     * EmployeeSchedule -- store() closes the prior current row (end_date
     * IS NULL) for the *same plan* before inserting a new one, so a
     * contribution-amount change becomes a new row instead of overwriting
     * history. Scoped per-plan rather than per-employee because, unlike
     * Employment, an employee can hold several concurrent enrollments
     * (HMO and a Loan at once) -- there's no single "current" row across
     * all of an employee's benefits, only within one plan.
     */
    public function up(): void
    {
        Schema::create('benefit_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benefit_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('employee_contribution', 10, 2)->nullable();
            $table->decimal('employer_contribution', 10, 2)->nullable();
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_enrollments');
    }
};
