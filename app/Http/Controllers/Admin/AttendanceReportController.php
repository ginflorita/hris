<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('attendance.view');

        $dateFrom = $request->date('date_from') ?? Carbon::now()->startOfMonth();
        $dateTo = $request->date('date_to') ?? Carbon::now()->endOfMonth();
        $companyId = $request->integer('company_id');

        $query = Attendance::with('employee')
            ->whereBetween('date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $records = $query->get();

        $summary = $records
            ->groupBy('employee_id')
            ->map(function ($rows) {
                $employee = $rows->first()->employee;

                return [
                    'employee' => $employee,
                    'present' => $rows->whereIn('status', ['present', 'late', 'undertime'])->count(),
                    'late' => $rows->where('status', 'late')->count(),
                    'absent' => $rows->where('status', 'absent')->count(),
                    'late_minutes' => $rows->sum('late_minutes'),
                    'undertime_minutes' => $rows->sum('undertime_minutes'),
                    'overtime_minutes' => $rows->sum('overtime_minutes'),
                ];
            })
            ->sortBy(fn ($row) => $row['employee']->full_name)
            ->values();

        return view('admin.attendance.report.index', [
            'summary' => $summary,
            'companies' => Company::orderBy('name')->get(),
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'companyId' => $companyId,
        ]);
    }
}
