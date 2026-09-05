<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A step names who can act on it, reusing this app's existing RBAC
 * rather than a parallel approver-assignment system: `Manager`
 * resolves to the workflow subject's own current manager (via
 * Employment.manager_id, the same relation Team data scope already
 * uses); `Permission` accepts anyone holding `required_permission` --
 * see CLAUDE.md "Workflow" for why a permission-name column, not a
 * role/user assignment table, was the right level to hook in at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('name');
            $table->string('approver_type');
            $table->string('required_permission')->nullable();
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
