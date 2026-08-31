<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprint §17: "Log: Payslip viewed / downloaded / printed /
     * exported." Only viewed/downloaded are actually produced by this
     * app (there's no separate print or export action) -- see
     * PortalPayslipController. Same shape as login_logs (Phase 3).
     */
    public function up(): void
    {
        Schema::create('payslip_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payroll_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_access_logs');
    }
};
