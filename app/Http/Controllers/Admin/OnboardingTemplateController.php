<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §9: a named, reusable checklist definition (company-scoped
 * CRUD, same shape as LeaveType/Holiday/PayrollGroup). Tasks are managed
 * from show() via add/edit/delete modals, the same pattern
 * ContributionRateTable's brackets use. Gated by recruitment.view/
 * recruitment.manage -- Onboarding is blueprint's own Phase 14 module
 * alongside Recruitment, so unlike Compensation this doesn't need to
 * borrow a permission group from elsewhere.
 */
class OnboardingTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('recruitment.view');

        return view('admin.recruitment.onboarding-templates.index', [
            'onboardingTemplates' => OnboardingTemplate::with('company')->withCount('tasks')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('recruitment.manage');

        return view('admin.recruitment.onboarding-templates.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $template = OnboardingTemplate::create($this->validated($request));

        return redirect()->route('admin.recruitment.onboarding-templates.show', $template)
            ->with('status', 'Onboarding template created. Add its tasks below.');
    }

    public function show(OnboardingTemplate $onboardingTemplate): View
    {
        $this->authorize('recruitment.view');

        return view('admin.recruitment.onboarding-templates.show', [
            'onboardingTemplate' => $onboardingTemplate->load('tasks'),
        ]);
    }

    public function edit(OnboardingTemplate $onboardingTemplate): View
    {
        $this->authorize('recruitment.manage');

        return view('admin.recruitment.onboarding-templates.edit', [
            'onboardingTemplate' => $onboardingTemplate,
            'companies' => $this->companies(),
        ]);
    }

    public function update(Request $request, OnboardingTemplate $onboardingTemplate): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $onboardingTemplate->update($this->validated($request, $onboardingTemplate));

        return redirect()->route('admin.recruitment.onboarding-templates.index')->with('status', 'Onboarding template updated.');
    }

    /**
     * Blocked while any employee has ever been assigned this template --
     * the same "deleting is blocked while children exist" rule
     * Organization's controllers enforce, so a checklist an employee's
     * onboarding progress still points at can't disappear out from under
     * them. Deactivate via is_active to retire a template instead.
     */
    public function destroy(OnboardingTemplate $onboardingTemplate): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_if($onboardingTemplate->employeeOnboardings()->exists(), 422, 'This template has already been assigned to an employee and cannot be deleted. Deactivate it instead.');

        $onboardingTemplate->tasks()->delete();
        $onboardingTemplate->delete();

        return redirect()->route('admin.recruitment.onboarding-templates.index')->with('status', 'Onboarding template deleted.');
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
    private function validated(Request $request, ?OnboardingTemplate $template = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // An unchecked checkbox submits no field at all -- absent means
        // "use the default" (true) on create, but "the box was there and
        // got unchecked" (false) on update. See CLAUDE.md "Organization".
        $validated['is_active'] = $request->boolean('is_active', $template === null);

        return $validated;
    }
}
