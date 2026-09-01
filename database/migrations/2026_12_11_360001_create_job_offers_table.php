<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An application can accumulate more than one job_offers row over time
     * (an offer can be re-extended after a decline), but only one Pending
     * at once -- enforced in JobOfferController::store() /
     * Application::hasPendingJobOffer(), not a DB constraint, the same
     * "app-level rule on top of the FK" approach PayrollPeriod's overlap
     * check uses. converted_employee_id/converted_at record the
     * hiring-conversion outcome directly on the offer row itself, the same
     * "freeze the outcome on the originating row" shape CoeRequest's
     * snapshot columns use for its own approval outcome.
     */
    public function up(): void
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employment_type');
            $table->string('work_arrangement')->nullable();
            $table->decimal('offered_salary', 12, 2);
            $table->date('start_date');
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('extended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->string('decision_reason')->nullable();
            $table->foreignId('converted_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
