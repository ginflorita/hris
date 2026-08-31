<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * v1 is one primary interviewer per row -- schedule multiple
     * interview rows for a panel rather than a separate interviewers
     * join table (see CLAUDE.md "Recruitment"). Since there's exactly
     * one interviewer per row, the outcome (rating/recommendation/
     * feedback) lives directly on this table too instead of a separate
     * interview_evaluations table -- there'd be nothing to join beyond
     * a 1:1 relationship.
     */
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interviewer_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('type');
            $table->dateTime('scheduled_at');
            $table->string('location')->nullable();
            $table->string('status')->default('scheduled');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('recommendation')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
