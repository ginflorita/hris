<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company-scoped CRUD, same shape as LeaveType/Holiday/PayrollGroup --
     * a named checklist definition. is_active lets a retired template stay
     * out of the "Assign Onboarding" picker without losing the history of
     * employees already assigned to it (destroy() is separately blocked
     * while any employee_onboardings row references it -- see
     * OnboardingTemplateController).
     */
    public function up(): void
    {
        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_templates');
    }
};
