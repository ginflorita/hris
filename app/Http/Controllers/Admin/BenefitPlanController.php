<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BenefitType;
use App\Http\Controllers\Controller;
use App\Models\BenefitPlan;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Company-scoped lookup, same shape as LeaveType/Holiday/Competency --
 * gated by benefits.view/benefits.manage, Benefits' own seeded
 * permission group (unlike Compensation or Career Development, no
 * borrowing needed here).
 */
class BenefitPlanController extends Controller
{
    public function index(): View
    {
        $this->authorize('benefits.view');

        return view('admin.benefits.plans.index', [
            'plans' => BenefitPlan::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('benefits.manage');

        return view('admin.benefits.plans.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('benefits.manage');

        BenefitPlan::create($this->validated($request));

        return redirect()->route('admin.benefits.plans.index')->with('status', 'Benefit plan created.');
    }

    public function edit(BenefitPlan $plan): View
    {
        $this->authorize('benefits.manage');

        return view('admin.benefits.plans.edit', ['plan' => $plan, 'companies' => $this->companies()]);
    }

    public function update(Request $request, BenefitPlan $plan): RedirectResponse
    {
        $this->authorize('benefits.manage');

        $plan->update($this->validated($request, $plan));

        return redirect()->route('admin.benefits.plans.index')->with('status', 'Benefit plan updated.');
    }

    public function destroy(BenefitPlan $plan): RedirectResponse
    {
        $this->authorize('benefits.manage');

        if ($plan->enrollments()->exists()) {
            return back()->withErrors(['plan' => 'Remove the employee enrollments using this plan before deleting it.']);
        }

        $plan->delete();

        return redirect()->route('admin.benefits.plans.index')->with('status', 'Benefit plan deleted.');
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies(): Collection
    {
        return Company::orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?BenefitPlan $plan = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('benefit_plans', 'name')->where('company_id', $request->input('company_id'))->ignore($plan?->id),
            ],
            'type' => ['required', Rule::enum(BenefitType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'eligibility_criteria' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $plan === null);

        return $validated;
    }
}
