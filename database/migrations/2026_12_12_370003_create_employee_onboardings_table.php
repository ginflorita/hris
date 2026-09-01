<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per checklist assigned to an employee. There's no status
     * column here -- completion is derived from whether every child
     * employee_onboarding_tasks row is_completed (see
     * EmployeeOnboarding::isComplete()/progressPercentage()), the same
     * "compute rather than duplicate" choice Application::
     * hasPendingJobOffer() makes for its own state check. Blueprint's
     * `employee_onboarding` (singular) is named `employee_onboardings`
     * here instead, following this app's consistent use of Eloquent's
     * default pluralization everywhere else rather than the ERD sketch's
     * exact string.
     */
    public function up(): void
    {
        Schema::create('employee_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_onboardings');
    }
};
