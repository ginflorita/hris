<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\JobPostingStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §8's pipeline: an Application is a specific applicant
 * against a specific posting, tracked separately from the Applicant
 * profile itself so the same person can be in the candidate pool for
 * more than one posting over time.
 */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('recruitment.view');

        $query = Application::with(['applicant', 'jobPosting.company'])->orderByDesc('applied_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($postingId = $request->integer('job_posting_id')) {
            $query->where('job_posting_id', $postingId);
        }

        return view('admin.recruitment.applications.index', [
            'applications' => $query->paginate(20)->withQueryString(),
            'postings' => JobPosting::orderByDesc('created_at')->get(),
            'filters' => $request->only(['status', 'job_posting_id']),
        ]);
    }

    /**
     * Reached from the applicant's own profile page (a modal, same
     * pattern as Employee's per-employee sub-resources), not a
     * standalone form -- the natural entry point is "apply this
     * candidate to a posting," not a blank two-picker form.
     */
    public function store(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $validated = $request->validate([
            'job_posting_id' => [
                'required',
                Rule::exists('job_postings', 'id')->where('status', JobPostingStatus::Published->value),
                Rule::unique('applications')->where('applicant_id', $applicant->id),
            ],
        ]);

        $applicant->applications()->create([
            'job_posting_id' => $validated['job_posting_id'],
            'status' => ApplicationStatus::Applied,
            'applied_at' => now(),
        ]);

        return redirect()->route('admin.recruitment.applicants.show', $applicant)->with('status', 'Application recorded.');
    }

    public function show(Application $application): View
    {
        $this->authorize('recruitment.view');

        $application->load([
            'applicant', 'jobPosting.company',
            'interviews.interviewer',
            'assessments.assessedBy',
            'jobOffers.extendedBy', 'jobOffers.position', 'jobOffers.convertedEmployee',
        ]);

        $companyId = $application->jobPosting->company_id;

        return view('admin.recruitment.applications.show', [
            'application' => $application,
            'interviewers' => Employee::orderBy('last_name')->get(),
            'departments' => Department::where('company_id', $companyId)->orderBy('name')->get(),
            'positions' => Position::where('company_id', $companyId)->orderBy('title')->get(),
        ]);
    }

    /**
     * Offered and Hired are excluded from the statuses this accepts --
     * they're now driven exclusively by the JobOffer lifecycle
     * (JobOfferController::store()/accept()) and hiring conversion
     * (JobOfferController::convert()) respectively, so this endpoint no
     * longer lets either be set directly. Without this, the plain PUT
     * here could mark an application Hired with no JobOffer or Employee
     * behind it at all.
     */
    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_if($application->status->isTerminal(), 422, 'This application has already reached a final status.');

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::enum(ApplicationStatus::class),
                Rule::notIn([ApplicationStatus::Offered->value, ApplicationStatus::Hired->value]),
            ],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ], [
            'status.not_in' => 'Offered and Hired are set by the job offer workflow, not chosen directly.',
        ]);

        $application->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === ApplicationStatus::Rejected->value ? $validated['rejection_reason'] : null,
        ]);

        return back()->with('status', 'Application status updated.');
    }
}
