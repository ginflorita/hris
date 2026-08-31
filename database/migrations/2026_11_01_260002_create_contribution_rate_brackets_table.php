<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_rate_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_rate_table_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2)->nullable();
            $table->decimal('employee_amount', 12, 2);
            $table->decimal('employer_amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_rate_brackets');
    }
};
