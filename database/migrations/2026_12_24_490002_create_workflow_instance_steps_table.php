<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `name`/`approver_type`/`required_permission` are snapshotted from the
 * `WorkflowStep` at the moment the instance is started, not read live
 * through `workflow_step_id` -- the same "freeze at the point that
 * matters" principle CoeRequest's snapshot columns already established
 * for Employment data (see CLAUDE.md "13d"). Without the snapshot, an
 * admin editing a step's approver after an instance already started
 * would silently change what an in-flight or historical approval
 * meant; `workflow_step_id` is kept purely as a soft backreference, not
 * the engine's source of truth for resolving who could act.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instance_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('name');
            $table->string('approver_type');
            $table->string('required_permission')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['workflow_instance_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instance_steps');
    }
};
