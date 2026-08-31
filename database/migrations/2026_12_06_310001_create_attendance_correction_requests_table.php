<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employee-initiated ("I think my time in is wrong, please fix it"),
     * distinct from attendance_correction_logs -- that table is an audit
     * trail written *during* an HR-side correction (AttendanceController
     * ::update()), not a request awaiting approval. Approving a row here
     * still goes through the exact same audit-logged correction path
     * (see App\Domain\Attendance\Services\AttendanceCorrectionService)
     * once accepted.
     */
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->time('requested_time_in')->nullable();
            $table->time('requested_time_out')->nullable();
            $table->string('requested_status');
            $table->text('reason');
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
        Schema::dropIfExists('attendance_correction_requests');
    }
};
