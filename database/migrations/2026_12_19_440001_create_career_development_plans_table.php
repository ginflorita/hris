<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprint names "Career development" as a Phase 15 function with no
     * further detail anywhere in the document (its table-of-contents
     * entry points at a section body that's actually Payroll Snapshot --
     * the two were never written). Modeled as a per-employee plan with
     * the same Active -> Achieved/Cancelled lifecycle shape
     * PerformanceImprovementPlan already established, since a career plan
     * is the same kind of forward-looking record with a bounded outcome.
     */
    public function up(): void
    {
        Schema::create('career_development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->date('target_date')->nullable();
            $table->text('development_actions');
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_development_plans');
    }
};
