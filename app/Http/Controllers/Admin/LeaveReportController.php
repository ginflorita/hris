<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LeaveReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('leave.view');

        $dateFrom = $request->date('date_from') ?? Carbon::now()->startOfYear();
        $dateTo = $request->date('date_to') ?? Carbon::now()->endOfYear();
        $companyId = $request->integer('company_id');

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->whereBetween('start_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $summary = $query->get()
            ->groupBy(fn ($request) => $request->employee_id.'-'.$request->leave_type_id)
            ->map(function ($rows) {
                return [
                    'employee' => $rows->first()->employee,
                    'leaveType' => $rows->first()->leaveType,
                    'requests' => $rows->count(),
                    'days' => $rows->sum('days_count'),
                ];
            })
            ->sortBy(fn ($row) => $row['employee']->full_name)
            ->values();

        return view('admin.leave.report.index', [
            'summary' => $summary,
            'companies' => Company::orderBy('name')->get(),
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'companyId' => $companyId,
        ]);
    }
}
