<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The template's own checklist definition -- managed from the
     * template's show page via add/edit/delete modals, the same pattern
     * ContributionRateTable's brackets use. Assigning a template to an
     * employee copies these rows into employee_onboarding_tasks rather
     * than referencing them directly, so editing a template later never
     * changes an in-progress hire's checklist out from under them.
     */
    public function up(): void
    {
        Schema::create('onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_template_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
    }
};
