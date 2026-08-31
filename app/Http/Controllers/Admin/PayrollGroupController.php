<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayFrequency;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollGroupController extends Controller
{
    public function index(): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.payroll-groups.index', [
            'payrollGroups' => PayrollGroup::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.payroll-groups.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('payroll.create');

        PayrollGroup::create($this->validated($request));

        return redirect()->route('admin.payroll.payroll-groups.index')->with('status', 'Payroll group created.');
    }

    public function edit(PayrollGroup $payrollGroup): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.payroll-groups.edit', ['payrollGroup' => $payrollGroup, 'companies' => $this->companies()]);
    }

    public function update(Request $request, PayrollGroup $payrollGroup): RedirectResponse
    {
        $this->authorize('payroll.create');

        $payrollGroup->update($this->validated($request, $payrollGroup));

        return redirect()->route('admin.payroll.payroll-groups.index')->with('status', 'Payroll group updated.');
    }

    public function destroy(PayrollGroup $payrollGroup): RedirectResponse
    {
        $this->authorize('payroll.create');

        if ($payrollGroup->payrollPeriods()->exists()) {
            return back()->withErrors(['payrollGroup' => 'Remove the payroll periods under this group before deleting it.']);
        }

        $payrollGroup->delete();

        return redirect()->route('admin.payroll.payroll-groups.index')->with('status', 'Payroll group deleted.');
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
    private function validated(Request $request, ?PayrollGroup $group = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('payroll_groups', 'code')->where('company_id', $request->input('company_id'))->ignore($group?->id),
            ],
            'pay_frequency' => ['required', Rule::enum(PayFrequency::class)],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $group === null);

        return $validated;
    }
}
