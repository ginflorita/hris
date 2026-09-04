<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first real `WorkflowInstance` subject -- an employee proposing a
 * change to a small, deliberately restrained set of their own bio
 * fields (mobile/email/civil status/nationality; not name/employee
 * number, which are identity fields, and not address/emergency
 * contact, which already have their own EmployeeAddress/EmployeeContact
 * sub-resources -- see CLAUDE.md "Workflow" 20c). Explicit
 * `requested_*` columns, not a JSON diff blob, matching
 * AttendanceCorrectionRequest's own "explicit proposed-value columns"
 * shape (Phase 13c) -- only the fields actually being changed are
 * non-null. **No status column** -- unlike AttendanceCorrectionRequest
 * (which predates this engine and owns its own status), this table's
 * status lives entirely on its `WorkflowInstance` via the polymorphic
 * `subject` relation, the whole point of this being the engine's first
 * real consumer rather than another bespoke approval table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_information_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('requested_mobile')->nullable();
            $table->string('requested_email')->nullable();
            $table->string('requested_civil_status')->nullable();
            $table->string('requested_nationality')->nullable();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_information_change_requests');
    }
};
