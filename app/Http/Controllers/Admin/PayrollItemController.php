<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Illuminate\View\View;

class PayrollItemController extends Controller
{
    public function show(PayrollItem $payrollItem): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.payroll-items.show', [
            'payrollItem' => $payrollItem->load(['employee', 'payrollPeriod', 'taxTable', 'lines', 'contributions']),
        ]);
    }
}
