<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprint §8: a job requisition is the headcount *request* --
     * pending HR approval before any recruiting or posting work starts.
     * department_id/position_id are nullable since a requisition can
     * predate a specific open position (e.g. "we need 2 more support
     * engineers, don't have a formal position row yet").
     */
    public function up(): void
    {
        Schema::create('job_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('openings_count')->default(1);
            $table->text('justification')->nullable();
            $table->date('target_start_date')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_requisitions');
    }
};
