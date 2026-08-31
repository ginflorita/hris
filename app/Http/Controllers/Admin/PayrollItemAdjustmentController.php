<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Payroll\Services\PayrollCalculationService;
use App\Enums\PayrollItemLineType;
use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A manual adjustment is just a PayrollItemLine with is_adjustment=true
 * -- see CLAUDE.md "Payroll" for why there's no separate payroll_
 * adjustments table. Only allowed while the item's period is ForReview
 * (Phase 11's only reachable post-processing state); Phase 12's approve/
 * finalize/lock states will need their own, stricter rule once they
 * exist.
 */
class PayrollItemAdjustmentController extends Controller
{
    public function store(Request $request, PayrollItem $payrollItem, PayrollCalculationService $service): RedirectResponse
    {
        $this->authorize('payroll.create');

        if ($error = $this->guardReviewable($payrollItem)) {
            return $error;
        }

        $validated = $request->validate([
            'type' => ['required', Rule::enum(PayrollItemLineType::class)],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $payrollItem->lines()->create([
            'type' => $validated['type'],
            'category' => 'adjustment',
            'label' => $validated['label'],
            'amount' => $validated['amount'],
            'is_adjustment' => true,
            'remarks' => $validated['remarks'],
            'created_by' => $request->user()->id,
        ]);

        $service->recalculateTotals($payrollItem);

        return back()->with('status', 'Adjustment added.');
    }

    public function destroy(PayrollItem $payrollItem, PayrollItemLine $line, PayrollCalculationService $service): RedirectResponse
    {
        $this->authorize('payroll.create');
        abort_unless($line->payroll_item_id === $payrollItem->id, 404);
        abort_unless($line->is_adjustment, 404);

        if ($error = $this->guardReviewable($payrollItem)) {
            return $error;
        }

        $line->delete();
        $service->recalculateTotals($payrollItem);

        return back()->with('status', 'Adjustment removed.');
    }

    private function guardReviewable(PayrollItem $payrollItem): ?RedirectResponse
    {
        if ($payrollItem->payrollPeriod->status !== PayrollPeriodStatus::ForReview) {
            return back()->withErrors(['payrollItem' => 'Adjustments can only be made while the period is For Review.']);
        }

        return null;
    }
}
