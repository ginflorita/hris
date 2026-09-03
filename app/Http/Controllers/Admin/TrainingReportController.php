<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrainingEnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TrainingEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Blueprint §3 item 59, "Training Reports". Same "reachable from the
 * Reports landing page only, no new sidebar row" shape as Recruitment/
 * Performance Reports -- see RecruitmentReportController's doc comment.
 * Gated by `training.view`. Aggregates across all enrollments rather
 * than picking one session/course the way Payroll/Performance Reports
 * pick a period/cycle -- training has no single "current period"
 * concept spanning every course the way PayrollPeriod/PerformanceCycle
 * do (a session's own dates don't group other courses' sessions), so a
 * company-wide snapshot (HR Report's shape, 19a) fits better than a
 * period selector.
 */
class TrainingReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('training.view');

        $companyId = $request->integer('company_id');

        $enrollments = TrainingEnrollment::query()
            ->whereHas('session', fn ($query) => $query->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
            ->get();

        $statusCounts = $enrollments->countBy(fn (TrainingEnrollment $enrollment) => $enrollment->status->value);

        $byStatus = collect(TrainingEnrollmentStatus::cases())->map(fn (TrainingEnrollmentStatus $status) => [
            'status' => $status,
            'count' => $statusCounts->get($status->value, 0),
        ]);

        $completedCount = $statusCounts->get(TrainingEnrollmentStatus::Completed->value, 0);

        $certificatesIssued = $enrollments->whereNotNull('certificate_number')->count();
        $certificatesExpiringSoon = $enrollments
            ->whereNotNull('certificate_expires_at')
            ->filter(fn (TrainingEnrollment $enrollment) => $enrollment->certificate_expires_at->between(now(), Carbon::now()->addDays(30)))
            ->count();

        return view('admin.reports.training.index', [
            'companies' => Company::orderBy('name')->get(),
            'companyId' => $companyId,
            'totalEnrollments' => $enrollments->count(),
            'byStatus' => $byStatus,
            'completionRate' => $enrollments->isNotEmpty() ? round($completedCount / $enrollments->count() * 100, 1) : null,
            'certificatesIssued' => $certificatesIssued,
            'certificatesExpiringSoon' => $certificatesExpiringSoon,
        ]);
    }
}
