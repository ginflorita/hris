<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same documentation gap as career_development_plans -- blueprint
     * names "Succession planning" with no functions list. Real-world
     * succession planning is usually organized by position ("who could
     * replace the VP of Engineering"), but this table is entered from
     * the employee side ("which positions is this person being groomed
     * for") to match every other Phase 15 entity's per-employee-tab
     * shape rather than adding a show() page to Position (Phase 5,
     * already shipped, not designed to need one). Same data either way
     * -- just queried from the other direction if a position-centric
     * view is ever needed.
     */
    public function up(): void
    {
        Schema::create('succession_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->string('readiness');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'position_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('succession_candidates');
    }
};
