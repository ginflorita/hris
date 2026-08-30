<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccrualFrequency;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeavePolicyController extends Controller
{
    public function index(): View
    {
        $this->authorize('leave.view');

        return view('admin.leave.policies.index', [
            'leavePolicies' => LeavePolicy::with(['company', 'leaveType'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('leave.create');

        return view('admin.leave.policies.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('leave.create');

        LeavePolicy::create($this->validated($request));

        return redirect()->route('admin.leave.policies.index')->with('status', 'Leave policy created.');
    }

    public function edit(LeavePolicy $policy): View
    {
        $this->authorize('leave.create');

        return view('admin.leave.policies.edit', ['leavePolicy' => $policy, ...$this->formData()]);
    }

    public function update(Request $request, LeavePolicy $policy): RedirectResponse
    {
        $this->authorize('leave.create');

        $policy->update($this->validated($request, $policy));

        return redirect()->route('admin.leave.policies.index')->with('status', 'Leave policy updated.');
    }

    public function destroy(LeavePolicy $policy): RedirectResponse
    {
        $this->authorize('leave.create');

        $policy->delete();

        return redirect()->route('admin.leave.policies.index')->with('status', 'Leave policy deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, leaveTypes: Collection<int, LeaveType>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'leaveTypes' => LeaveType::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LeavePolicy $policy = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $request->input('company_id'))],
            'name' => ['required', 'string', 'max:255'],
            'accrual_rate' => ['required', 'numeric', 'min:0', 'max:31'],
            'accrual_frequency' => ['required', Rule::enum(AccrualFrequency::class)],
            'max_balance' => ['nullable', 'numeric', 'min:0'],
            'carry_over_days' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $policy === null);

        return $validated;
    }
}
