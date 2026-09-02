<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprint §23 lists Enrollment, Attendance, and Certificates as
     * three separate functions; here they're one table. "Attendance" is
     * folded into `status` (Enrolled -> Completed/Cancelled/NoShow --
     * Completed *is* "attended"), the same way Interview's outcome
     * columns live directly on the interview row rather than a separate
     * evaluation table. "Certificates" is three nullable columns on the
     * same row rather than a wrapper table, since a session enrollment
     * has at most one certificate, not a repeating collection.
     */
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('enrolled');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->string('certificate_number')->nullable();
            $table->date('certificate_issued_at')->nullable();
            $table->date('certificate_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'training_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
