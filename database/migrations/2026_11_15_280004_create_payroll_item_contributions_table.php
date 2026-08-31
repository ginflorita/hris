<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contribution_rate_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contribution_rate_bracket_id')->constrained()->cascadeOnDelete();
            $table->string('agency');
            $table->decimal('employee_amount', 12, 2);
            $table->decimal('employer_amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_contributions');
    }
};
