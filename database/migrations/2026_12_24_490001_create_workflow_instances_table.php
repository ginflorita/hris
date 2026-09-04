<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per real request going through a `WorkflowDefinition`. The
 * `subject` (polymorphic, plain class-name storage -- same convention
 * `audit_logs.auditable_type` already established, no morph map) is the
 * actual record the workflow governs, e.g. an
 * `EmployeeInformationChangeRequest`. `current_step_order` is the step
 * currently awaiting action, null once the instance is terminal --
 * there's nothing "current" left once it's Approved/Rejected/Cancelled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('in_progress');
            $table->unsignedSmallInteger('current_step_order')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
