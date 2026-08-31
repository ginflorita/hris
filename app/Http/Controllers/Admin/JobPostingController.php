<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobPostingStatus;
use App\Enums\JobRequisitionStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('recruitment.view');

        $query = JobPosting::with(['jobRequisition.department', 'company'])->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.recruitment.postings.index', [
            'postings' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('recruitment.manage');

        return view('admin.recruitment.postings.create', ['requisitions' => $this->approvedRequisitions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $validated = $request->validate([
            'job_requisition_id' => [
                'required',
                Rule::exists('job_requisitions', 'id')->where('status', JobRequisitionStatus::Approved->value),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'closes_at' => ['nullable', 'date'],
        ]);

        $requisition = JobRequisition::findOrFail($validated['job_requisition_id']);

        JobPosting::create([
            ...$validated,
            'company_id' => $requisition->company_id,
            'is_internal' => $request->boolean('is_internal'),
            'status' => JobPostingStatus::Draft,
        ]);

        return redirect()->route('admin.recruitment.postings.index')->with('status', 'Job posting created.');
    }

    public function edit(JobPosting $posting): View
    {
        $this->authorize('recruitment.manage');

        $posting->load(['jobRequisition.department', 'jobRequisition.position']);

        return view('admin.recruitment.postings.edit', ['posting' => $posting]);
    }

    public function update(Request $request, JobPosting $posting): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'closes_at' => ['nullable', 'date'],
        ]);

        $posting->update([
            ...$validated,
            'is_internal' => $request->boolean('is_internal'),
        ]);

        return redirect()->route('admin.recruitment.postings.index')->with('status', 'Job posting updated.');
    }

    public function publish(JobPosting $posting): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($posting->status === JobPostingStatus::Draft, 422, 'Only a draft posting can be published.');

        $posting->update(['status' => JobPostingStatus::Published, 'published_at' => now()]);

        return back()->with('status', 'Job posting published.');
    }

    public function close(JobPosting $posting): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($posting->status === JobPostingStatus::Published, 422, 'Only a published posting can be closed.');

        $posting->update(['status' => JobPostingStatus::Closed]);

        return back()->with('status', 'Job posting closed.');
    }

    /**
     * @return Collection<int, JobRequisition>
     */
    private function approvedRequisitions()
    {
        return JobRequisition::with('company')->where('status', JobRequisitionStatus::Approved)->orderByDesc('approved_at')->get();
    }
}
