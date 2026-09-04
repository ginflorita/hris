<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blueprint §27 ("Workflow Engine") gives no field-level spec for its
 * six suggested tables -- just names and a list of processes it should
 * support (Leave, Overtime, Salary Adjustment, Promotion, COE, Employee
 * Information Change, Document Request, Training Request). See
 * CLAUDE.md "Workflow" for the full design reasoning; in short:
 * `workflow_actions`/`workflow_comments` are collapsed into
 * `workflow_instance_steps` (a v1 step is acted on exactly once, so a
 * separate audit-of-the-audit table would be redundant), the same
 * "collapse redundant layers" call this app makes everywhere else.
 * `process_type` identifies which real feature a definition serves
 * (`App\Enums\WorkflowProcessType`, mirroring blueprint's own 8 names)
 * so a consumer can look up "the active definition for my process" per
 * company without a fragile name match.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('process_type');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definitions');
    }
};
