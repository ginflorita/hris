<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versioned, effective-dated — see CLAUDE.md "Payroll" for why rates
     * are never hard-coded into calculation code (blueprint §39).
     */
    public function up(): void
    {
        Schema::create('contribution_rate_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('agency');
            $table->string('name');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_rate_tables');
    }
};
