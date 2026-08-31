<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A posting always belongs to an (approved) requisition -- see
     * JobPostingController::store()'s validation, not a DB constraint,
     * same "app-level rule on top of the FK" approach Organization uses
     * for its own hierarchy checks. company_id is denormalized from the
     * requisition (same pattern as every Organization entity) so postings
     * can be queried/scoped without joining through job_requisitions.
     */
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
