<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A pivot onto the existing employee_dependents table (Phase 6)
     * rather than a new dependent concept scoped to benefits -- "which of
     * this employee's already-recorded dependents are covered under this
     * enrollment" is the same person, just flagged covered-or-not per
     * plan.
     */
    public function up(): void
    {
        Schema::create('benefit_enrollment_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_dependent_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['benefit_enrollment_id', 'employee_dependent_id'], 'benefit_enrollment_dependent_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_enrollment_dependents');
    }
};
