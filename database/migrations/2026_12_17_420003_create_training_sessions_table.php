<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `company_id` is denormalized from the parent course (same as every
     * Organization entity below Company) rather than joined through --
     * lets sessions be listed/scoped per company without a join.
     * "Cost" (blueprint §23's own bullet) lives here, not on the course:
     * a course's real-world cost varies by provider/session/date, so
     * it's only known once a concrete session is scheduled.
     */
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_course_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
