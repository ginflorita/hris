<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No separate "KPIs" table -- blueprint §22 lists Goals and KPIs as
     * separate functions, but a KPI is just a goal with a measurable
     * target; target_value/actual_value/unit are nullable so a plain
     * qualitative goal can leave them blank. Same "one flexible table,
     * not two near-identical ones" call CompensationItem made.
     */
    public function up(): void
    {
        Schema::create('performance_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date')->nullable();
            $table->unsignedTinyInteger('weight')->nullable();
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('status')->default('not_started');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_goals');
    }
};
