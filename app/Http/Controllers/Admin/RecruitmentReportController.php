<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\JobRequisitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §3 item 57, "Recruitment Reports". Blueprint's own admin
 * nav sketch (the REPORTS section CLAUDE.md's Reports section already
 * quotes) only scaffolds five report slots -- HR/Attendance/Leave/
 * Payroll/Analytics -- with no Recruitment/Performance/Training row of
 * its own, even though §3's numbered module list names all eight. This
 * report exists (per that list) but is reachable only from the Reports
 * landing page, not a new sidebar row -- the same "built, but not every
 * built page gets its own sidebar entry" precedent Interviews/
 * Assessments (Phase 14c) and Career/Succession (Phase 15g) already
 * established. Gated by `recruitment.view`, matching Payroll Report's
 * (19b) "reuse the module's own permission" choice over `reports.view`.
 */
class RecruitmentReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('recruitment.view');

        $companyId = $request->integer('company_id');

        $applications = Application::query()
            ->whereHas('jobPosting', fn ($query) => $query->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
            ->get();

        $statusCounts = $applications->countBy(fn (Application $application) => $application->status->value);

        $pipeline = collect(ApplicationStatus::cases())->map(fn (ApplicationStatus $status) => [
            'status' => $status,
            'count' => $statusCounts->get($status->value, 0),
        ]);

        $requisitions = JobRequisition::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get();

        $requisitionsByStatus = collect(JobRequisitionStatus::cases())->map(fn (JobRequisitionStatus $status) => [
            'status' => $status,
            'count' => $requisitions->where('status', $status)->count(),
        ]);

        $openPostings = JobPosting::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->where('status', 'published')
            ->count();

        return view('admin.reports.recruitment.index', [
            'companies' => Company::orderBy('name')->get(),
            'companyId' => $companyId,
            'pipeline' => $pipeline,
            'requisitionsByStatus' => $requisitionsByStatus,
            'totalApplications' => $applications->count(),
            'openPostings' => $openPostings,
            'hiredCount' => $statusCounts->get(ApplicationStatus::Hired->value, 0),
        ]);
    }
}
