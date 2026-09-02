<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company-scoped lookup, same shape as LeaveType/Holiday -- unique on
     * (company_id, name) rather than a code: unlike Organization's
     * hierarchy entities, nothing cross-references a competency by a
     * compact code, so inventing one would be an unused field.
     */
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
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
        Schema::dropIfExists('competencies');
    }
};
