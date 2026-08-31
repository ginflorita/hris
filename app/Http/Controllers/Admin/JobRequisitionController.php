<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobRequisitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobRequisition;
use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §8: a requisition is a headcount *request*, pending HR
 * approval before recruiting starts -- create()/store() only ever
 * produce a Pending row; approve()/reject() are the only way its status
 * moves, same request-then-decide shape as Leave/Overtime/Attendance
 * Correction/COE. No edit() -- once submitted, a requisition is either
 * approved or rejected, not revised (resubmit a new one instead, same
 * as this app's other request types).
 */
class JobRequisitionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('recruitment.view');

        $query = JobRequisition::with(['company', 'department', 'position'])->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.recruitment.requisitions.index', [
            'requisitions' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('recruitment.manage');

        return view('admin.recruitment.requisitions.create', [
            'companies' => $this->companies(),
            'departments' => Department::with('company')->orderBy('name')->get(),
            'positions' => Position::with('company')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $request->input('company_id'))],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where('company_id', $request->input('company_id'))],
            'openings_count' => ['required', 'integer', 'min:1'],
            'justification' => ['nullable', 'string', 'max:2000'],
            'target_start_date' => ['nullable', 'date'],
        ]);

        JobRequisition::create([
            ...$validated,
            'status' => JobRequisitionStatus::Pending,
            'requested_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.recruitment.requisitions.index')->with('status', 'Job requisition submitted.');
    }

    public function approve(Request $request, JobRequisition $requisition): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($requisition->status === JobRequisitionStatus::Pending, 422, 'Only a pending requisition can be approved.');

        $requisition->update([
            'status' => JobRequisitionStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Job requisition approved.');
    }

    public function reject(Request $request, JobRequisition $requisition): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($requisition->status === JobRequisitionStatus::Pending, 422, 'Only a pending requisition can be rejected.');

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $requisition->update([
            'status' => JobRequisitionStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'Job requisition rejected.');
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies(): Collection
    {
        return Company::orderBy('name')->get();
    }
}
