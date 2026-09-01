<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\CivilStatus;
use App\Enums\EmploymentChangeType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\JobOfferStatus;
use App\Enums\WorkArrangement;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employee;
use App\Models\JobOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §8's last pipeline step before Hired. store() extends a new
 * offer and moves the Application to Offered; accept()/decline() record
 * the candidate's decision (recorded by HR -- there's no candidate-facing
 * portal); rescind() lets HR withdraw an unanswered offer. convert() is
 * the hiring-conversion step CLAUDE.md's "not built yet" bullet called
 * out: turning an Accepted offer into a real Employee + Employment row
 * (Phase 6/7's tables), the actual integration point back from
 * Recruitment into Core HR.
 */
class JobOfferController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_if($application->status->isTerminal(), 422, 'This application has already reached a final status.');
        abort_if($application->hasPendingJobOffer(), 422, 'This application already has an open offer awaiting a response.');

        $companyId = $application->jobPosting->company_id;

        $validated = $request->validate([
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where('company_id', $companyId)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'work_arrangement' => ['nullable', Rule::enum(WorkArrangement::class)],
            'offered_salary' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->jobOffers()->create([
            ...$validated,
            'status' => JobOfferStatus::Pending,
            'extended_by' => $request->user()->id,
        ]);

        $application->update(['status' => ApplicationStatus::Offered]);

        return back()->with('status', 'Offer extended.');
    }

    public function accept(Request $request, Application $application, JobOffer $offer): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($offer->application_id === $application->id, 404);
        abort_unless($offer->status === JobOfferStatus::Pending, 422, 'Only a pending offer can be accepted.');

        $offer->update([
            'status' => JobOfferStatus::Accepted,
            'responded_at' => now(),
        ]);

        return back()->with('status', 'Offer accepted. It can now be converted to an employee record.');
    }

    public function decline(Request $request, Application $application, JobOffer $offer): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($offer->application_id === $application->id, 404);
        abort_unless($offer->status === JobOfferStatus::Pending, 422, 'Only a pending offer can be declined.');

        $validated = $request->validate(['decision_reason' => ['required', 'string', 'max:500']]);

        $offer->update([
            'status' => JobOfferStatus::Declined,
            'responded_at' => now(),
            'decision_reason' => $validated['decision_reason'],
        ]);

        return back()->with('status', 'Offer declined.');
    }

    public function rescind(Request $request, Application $application, JobOffer $offer): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($offer->application_id === $application->id, 404);
        abort_unless($offer->status === JobOfferStatus::Pending, 422, 'Only a pending offer can be rescinded.');

        $validated = $request->validate(['decision_reason' => ['required', 'string', 'max:500']]);

        $offer->update([
            'status' => JobOfferStatus::Rescinded,
            'responded_at' => now(),
            'decision_reason' => $validated['decision_reason'],
        ]);

        return back()->with('status', 'Offer rescinded.');
    }

    /**
     * Requires employees.create on top of recruitment.manage -- creating
     * the actual Employee master record is exactly what that permission
     * gates everywhere else in the app, and no seeded role currently
     * holds both (Recruitment Officer doesn't have employees.create; HR
     * Administrator/HR Staff don't have recruitment.manage). That's
     * intentional, the same "permission exists, nothing's granted it by
     * default yet" pattern CLAUDE.md documents for organization.manage --
     * not a bug to fix here.
     */
    public function convertForm(Application $application, JobOffer $offer): View
    {
        $this->authorize('recruitment.manage');
        $this->authorize('employees.create');
        abort_unless($offer->application_id === $application->id, 404);
        abort_unless($offer->status === JobOfferStatus::Accepted, 422, 'Only an accepted offer can be converted.');
        abort_if($offer->converted_at !== null, 422, 'This offer has already been converted to an employee record.');

        $offer->load(['department', 'position']);
        $application->load(['applicant', 'jobPosting.company']);

        return view('admin.recruitment.offers.convert', ['application' => $application, 'offer' => $offer]);
    }

    /**
     * First/last name, email, and mobile come from the Applicant record,
     * not re-entered -- only employee_number (genuinely new and required)
     * plus the handful of bio fields Applicant never collected (mirrors
     * Employee::create()'s own optional fields) are gathered here.
     * Reusing EmployeeController's form would mean either accepting a
     * caller-supplied company_id/employee bio for a record this flow
     * should fully control, or forking it anyway -- not worth it for a
     * few extra fields.
     */
    public function convert(Request $request, Application $application, JobOffer $offer): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        $this->authorize('employees.create');
        abort_unless($offer->application_id === $application->id, 404);
        abort_unless($offer->status === JobOfferStatus::Accepted, 422, 'Only an accepted offer can be converted.');
        abort_if($offer->converted_at !== null, 422, 'This offer has already been converted to an employee record.');

        $companyId = $application->jobPosting->company_id;
        $applicant = $application->applicant;

        $validated = $request->validate([
            'employee_number' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_number')->where('company_id', $companyId),
            ],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'nationality' => ['nullable', 'string', 'max:255'],
        ]);

        if ($applicant->email !== null
            && Employee::where('company_id', $companyId)->where('email', $applicant->email)->exists()) {
            return back()
                ->withErrors(['employee_number' => "An employee at this company already uses the applicant's email ({$applicant->email}). Update the applicant's email before converting."])
                ->withInput();
        }

        $employee = DB::transaction(function () use ($validated, $applicant, $companyId, $offer, $application, $request) {
            $employee = Employee::create([
                ...$validated,
                'company_id' => $companyId,
                'first_name' => $applicant->first_name,
                'last_name' => $applicant->last_name,
                'email' => $applicant->email,
                'mobile' => $applicant->phone,
            ]);

            $employee->employments()->create([
                'company_id' => $companyId,
                'department_id' => $offer->department_id,
                'position_id' => $offer->position_id,
                'employment_type' => $offer->employment_type,
                'work_arrangement' => $offer->work_arrangement,
                'status' => EmploymentStatus::Active,
                'change_type' => EmploymentChangeType::Hire,
                'basic_salary' => $offer->offered_salary,
                'effective_date' => $offer->start_date,
                'created_by' => $request->user()->id,
            ]);

            $offer->update(['converted_employee_id' => $employee->id, 'converted_at' => now()]);
            $application->update(['status' => ApplicationStatus::Hired]);

            return $employee;
        });

        return redirect()->route('admin.employees.show', $employee)->with('status', 'Employee record created from accepted offer.');
    }
}
