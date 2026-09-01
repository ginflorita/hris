<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A per-hire snapshot of the template's tasks at assignment time
     * (title/description/sequence copied, not referenced) plus the
     * completion state itself. is_completed is a plain boolean, not an
     * enum -- a task is genuinely binary (done or not), unlike
     * Assessment's pending/passed/failed which needed a third state.
     */
    public function up(): void
    {
        Schema::create('employee_onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_onboarding_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_onboarding_tasks');
    }
};
