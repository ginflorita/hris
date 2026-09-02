<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately a separate table from `competencies`, not collapsed
     * into one table with a type enum the way CompensationItem/
     * PerformanceReview collapse their own near-identical variants: those
     * collapses all shared one downstream consumer that treats the
     * variants interchangeably (a payroll line, a cycle's review list).
     * A skill and a competency don't -- blueprint §23 ties skills to
     * training/certificates/expiration reminders, a role competencies
     * don't play, so keeping them separate avoids a table whose rows mean
     * two different things depending on a type column most queries would
     * have to filter on anyway.
     */
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
