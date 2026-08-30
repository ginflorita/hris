<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Effective-dated, append-only: a new row is inserted for every
     * lifecycle event (hire, promotion, transfer, salary change,
     * regularization, separation) rather than updating the previous one
     * in place — see CLAUDE.md "Employment" for why. The previously
     * current row (end_date null) gets its end_date set to the new row's
     * effective_date - 1 day in the same transaction that inserts it.
     */
    public function up(): void
    {
        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('employment_type');
            $table->string('work_arrangement')->nullable();
            $table->string('status');
            $table->string('change_type');

            $table->date('probation_ends_at')->nullable();
            $table->date('regularized_at')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('separation_reason')->nullable();
            $table->text('remarks')->nullable();

            $table->date('effective_date');
            $table->date('end_date')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
