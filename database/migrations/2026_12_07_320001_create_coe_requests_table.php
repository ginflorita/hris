<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprint §25: Request COE -> HR Approval -> Generate PDF ->
     * Available in Portal. The snapshot_* columns are filled in at
     * approve() time and are what the PDF is rendered from (both in the
     * portal and on re-download) -- never from the employee's *current*
     * Employment, which can keep changing after a certificate is issued.
     * Freezing the snapshot at approval is what makes a re-download of an
     * already-issued COE show the same thing it did on day one, the same
     * "never silently change a record once it's historical" principle
     * CLAUDE.md applies to compensation and payroll.
     */
    public function up(): void
    {
        Schema::create('coe_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('purpose')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('snapshot_position')->nullable();
            $table->string('snapshot_department')->nullable();
            $table->string('snapshot_employment_status')->nullable();
            $table->date('snapshot_date_hired')->nullable();
            $table->decimal('snapshot_monthly_salary', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_requests');
    }
};
