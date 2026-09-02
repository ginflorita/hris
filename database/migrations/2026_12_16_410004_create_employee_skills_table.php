<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('proficiency_level');
            $table->date('assessed_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skills');
    }
};
