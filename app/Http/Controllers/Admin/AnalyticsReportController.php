<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PerformanceReview;
use App\Models\TrainingEnrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §3 item 60, "Workforce Analytics" -- the last of the five
 * report slots blueprint's own admin nav sketch scaffolds (see this
 * file's Reports section), and the last Phase 19 slice. Deliberately a
 * single glance-level page, not an eighth detailed report: every number
 * here is a top-line count or average already computable from a query
 * this app's other reports (19a-19c) already run in more detail --
 * Analytics' whole value is putting one of each side by side, not
 * duplicating any of their breakdowns.
 *
 * Gated by `reports.view` alone, like HR Report (19a) -- deliberately
 * *not* `payroll.view`. "Workforce" analytics stays headcount/people
 * metrics on purpose; no payroll cost figure is shown here, so there's
 * nothing that would need 19b's tighter gate. Adding a payroll cost
 * tile later is a real, sized follow-up, not an oversight -- it would
 * need the same `payroll.view` layering 19b already established, which
 * this single-permission page doesn't have a way to express per-tile.
 */
class AnalyticsReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('reports.view');

        $companyId = $request->integer('company_id');

        $ratedReviews = PerformanceReview::query()
            ->whereNotNull('rating')
            ->whereHas('employee', fn ($query) => $query->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
            ->get();

        $enrollments = TrainingEnrollment::query()
            ->whereHas('session', fn ($query) => $query->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
            ->get();

        return view('admin.reports.analytics.index', [
            'companies' => Company::orderBy('name')->get(),
            'companyId' => $companyId,
            'activeEmployees' => Employee::query()
                ->whereNull('archived_at')
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->count(),
            'openPostings' => JobPosting::query()
                ->where('status', 'published')
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->count(),
            'pendingRequisitions' => JobRequisition::query()
                ->where('status', 'pending')
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->count(),
            'pendingLeaveRequests' => LeaveRequest::query()
                ->where('status', 'pending')
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->count(),
            'pendingOvertimeRequests' => OvertimeRequest::query()
                ->where('status', 'pending')
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->count(),
            'averagePerformanceRating' => $ratedReviews->isNotEmpty() ? round($ratedReviews->avg('rating'), 2) : null,
            'trainingCompletionRate' => $enrollments->isNotEmpty()
                ? round($enrollments->where('status', 'completed')->count() / $enrollments->count() * 100, 1)
                : null,
        ]);
    }
}
