<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Payroll\Services\PayrollCalculationService;
use App\Domain\Security\Services\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollGroup;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Notifications\PayslipPublished;
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
            'payrollPeriod' => $payrollPeriod->load([
                'company', 'payrollGroup', 'processedBy', 'submittedBy', 'approvedBy', 'finalizedBy', 'lockedBy', 'publishedBy',
            ]),
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

    /**
     * ForReview -> ForApproval: the reviewer is done (adjustments, if
     * any, are made -- see PayrollItemAdjustmentController) and hands
     * the period to an approver. No recalculation happens here.
     */
    public function submitForApproval(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.process');
        abort_unless($payrollPeriod->status === PayrollPeriodStatus::ForReview, 422, 'Only a period For Review can be submitted for approval.');

        $payrollPeriod->update([
            'status' => PayrollPeriodStatus::ForApproval,
            'submitted_for_approval_at' => now(),
            'submitted_by' => request()->user()->id,
        ]);

        return back()->with('status', 'Submitted for approval.');
    }

    public function approve(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.approve');
        abort_unless($payrollPeriod->status === PayrollPeriodStatus::ForApproval, 422, 'Only a period For Approval can be approved.');

        $payrollPeriod->update([
            'status' => PayrollPeriodStatus::Approved,
            'approved_at' => now(),
            'approved_by' => request()->user()->id,
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Payroll period approved.');
    }

    /**
     * Sends the period back to ForReview (not all the way to Draft) --
     * adjustments and Reprocess are both still available there, so a
     * rejected period is immediately actionable again.
     */
    public function reject(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.approve');
        abort_unless($payrollPeriod->status === PayrollPeriodStatus::ForApproval, 422, 'Only a period For Approval can be rejected.');

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $payrollPeriod->update([
            'status' => PayrollPeriodStatus::ForReview,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'Payroll period sent back for review.');
    }

    public function finalize(PayrollPeriod $payrollPeriod, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorize('payroll.finalize');
        abort_unless($payrollPeriod->status === PayrollPeriodStatus::Approved, 422, 'Only an approved period can be finalized.');

        $payrollPeriod->update([
            'status' => PayrollPeriodStatus::Finalized,
            'finalized_at' => now(),
            'finalized_by' => request()->user()->id,
        ]);

        $auditLogger->log(request()->user(), AuditAction::Finalized, 'Payroll', $payrollPeriod,
            ['status' => PayrollPeriodStatus::Approved->value],
            ['status' => PayrollPeriodStatus::Finalized->value],
        );

        return back()->with('status', 'Payroll period finalized -- it is now immutable.');
    }

    public function lock(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.lock');
        abort_unless($payrollPeriod->status === PayrollPeriodStatus::Finalized, 422, 'Only a finalized period can be locked.');

        $payrollPeriod->update([
            'status' => PayrollPeriodStatus::Locked,
            'locked_at' => now(),
            'locked_by' => request()->user()->id,
        ]);

        return back()->with('status', 'Payroll period locked.');
    }

    /**
     * Makes this period's PayrollItems visible to employees as payslips
     * (Phase 12's digital payslip portal reads Published periods only).
     * Reuses payroll.lock -- the seeded catalog has no dedicated
     * "publish" permission and Publish immediately follows Lock in
     * blueprint §14's lifecycle diagram, so it's the same actor/step in
     * practice.
     */
    public function publish(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('payroll.lock');
        abort_unless($payrollPeriod->status === PayrollPeriodStatus::Locked, 422, 'Only a locked period can be published.');

        $payrollPeriod->update([
            'status' => PayrollPeriodStatus::Published,
            'published_at' => now(),
            'published_by' => request()->user()->id,
        ]);

        $payrollPeriod->payrollItems()->with('employee.user')->get()
            ->each(function (PayrollItem $item) {
                $item->employee->user?->notify(new PayslipPublished($item));
            });

        return back()->with('status', 'Payroll period published -- payslips are now visible to employees.');
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
