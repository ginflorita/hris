<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per employee per competency, updated in place on
     * reassessment rather than append-only -- there's no blueprint
     * requirement to preserve every past rating the way compensation/
     * employment history must, so this follows Attendance's "correct in
     * place" precedent instead of Employment's "insert a new row."
     */
    public function up(): void
    {
        Schema::create('employee_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->string('proficiency_level');
            $table->date('assessed_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_competencies');
    }
};
