<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately no company_id -- unlike Employee (always exactly one
     * company), an applicant is a candidate-pool profile that can apply
     * to postings across different companies (multi-company support,
     * blueprint §40). Company-scoping happens per Application, via
     * job_posting -> job_requisition -> company_id, not on the applicant
     * record itself.
     */
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('resume_original_filename')->nullable();
            $table->string('source')->default('other');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
