<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LeaveCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('leave.view');

        $monthStart = $request->date('month') ?? Carbon::now()->startOfMonth();
        $monthStart = $monthStart->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $companyId = $request->integer('company_id');

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', '<=', $monthEnd->format('Y-m-d'))
            ->where('end_date', '>=', $monthStart->format('Y-m-d'));

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return view('admin.leave.calendar.index', [
            'leaveRequests' => $query->orderBy('start_date')->get(),
            'companies' => Company::orderBy('name')->get(),
            'month' => $monthStart->format('Y-m-d'),
            'monthLabel' => $monthStart->format('F Y'),
            'companyId' => $companyId,
        ]);
    }
}
