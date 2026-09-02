<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per departure, advancing through App\Enums\OffboardingStatus
     * ::sequence() via a single generic advance() action rather than ten
     * near-identical guarded methods. Only two of the ten pipeline steps
     * get their own audit columns (approved_at/approved_by, matching
     * every other approval flow in this app) plus a generic
     * status_changed_at that's overwritten on every step -- inventing a
     * distinct timestamp column for each of the other eight stages would
     * be tracking detail blueprint's own flowchart never asks for (unlike
     * Payroll's Phase 12a state machine, which got one per step because
     * blueprint explicitly modeled each as its own audited business
     * event).
     */
    public function up(): void
    {
        Schema::create('offboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('resignation_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('resignation');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_requests');
    }
};
