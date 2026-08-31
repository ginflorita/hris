<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Payroll\Services\PayrollCalculationService;
use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollGroup;
use App\Models\PayrollPeriod;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function index(): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.payroll-periods.index', [
            'payrollPeriods' => PayrollPeriod::with(['company', 'payrollGroup'])->orderByDesc('start_date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.payroll-periods.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('payroll.create');

        $validated = $this->validated($request);
        $validated['status'] = PayrollPeriodStatus::Draft;

        PayrollPeriod::create($validated);

        return redirect()->route('admin.payroll.payroll-periods.index')->with('status', 'Payroll period created.');
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.payroll-periods.show', [
            'payrollPeriod' => $payrollPeriod->load(['company', 'payrollGroup', 'processedBy']),
            'payrollItems' => $payrollPeriod->payrollItems()->with('employee')->orderBy('employee_id')->get(),
        ]);
    }

    public function process(PayrollPeriod $payrollPeriod, PayrollCalculationService $service): RedirectResponse
    {
        $this->authorize('payroll.process');

        if (! in_array($payrollPeriod->status, [PayrollPeriodStatus::Draft, PayrollPeriodStatus::ForReview], true)) {
            return back()->withErrors(['payrollPeriod' => 'This period can no longer be (re)processed -- it has moved past review.']);
        }

        $count = $service->process($payrollPeriod, request()->user());

        return redirect()->route('admin.payroll.payroll-periods.show', $payrollPeriod)
            ->with('status', "Processed {$count} employee(s). Period is now For Review.");
    }

    public function edit(PayrollPeriod $payrollPeriod): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.payroll-periods.edit', ['payrollPeriod' => $payrollPeriod, ...$this->formData()]);
    }

    public function update(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.create');

        if ($payrollPeriod->status !== PayrollPeriodStatus::Draft) {
            return back()->withErrors(['payrollPeriod' => 'Only a draft period can be edited -- this one has already started processing.']);
        }

        $payrollPeriod->update($this->validated($request, $payrollPeriod));

        return redirect()->route('admin.payroll.payroll-periods.index')->with('status', 'Payroll period updated.');
    }

    public function destroy(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.create');

        if ($payrollPeriod->status !== PayrollPeriodStatus::Draft) {
            return back()->withErrors(['payrollPeriod' => 'Only a draft period can be deleted -- this one has already started processing.']);
        }

        $payrollPeriod->delete();

        return redirect()->route('admin.payroll.payroll-periods.index')->with('status', 'Payroll period deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, payrollGroups: Collection<int, PayrollGroup>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'payrollGroups' => PayrollGroup::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PayrollPeriod $period = null): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'payroll_group_id' => ['required', Rule::exists('payroll_groups', 'id')->where('company_id', $request->input('company_id'))],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', $this->noOverlapRule($request, $period)],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['required', 'date', 'after_or_equal:end_date'],
        ]);
    }

    private function noOverlapRule(Request $request, ?PayrollPeriod $period): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request, $period) {
            $overlaps = PayrollPeriod::query()
                ->where('payroll_group_id', $request->input('payroll_group_id'))
                ->where('status', '!=', PayrollPeriodStatus::Cancelled->value)
                ->when($period, fn ($query) => $query->whereKeyNot($period->id))
                ->where('start_date', '<=', $request->input('end_date'))
                ->where('end_date', '>=', $request->input('start_date'))
                ->exists();

            if ($overlaps) {
                $fail('This date range overlaps an existing period in the same payroll group.');
            }
        };
    }
}
