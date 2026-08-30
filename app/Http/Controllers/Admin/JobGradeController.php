<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobGradeController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.job-grades.index', [
            'jobGrades' => JobGrade::with('company')->orderBy('company_id')->orderBy('rank')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.job-grades.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        JobGrade::create($this->validated($request));

        return redirect()->route('admin.organization.job-grades.index')->with('status', 'Job grade created.');
    }

    public function edit(JobGrade $jobGrade): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.job-grades.edit', ['jobGrade' => $jobGrade, 'companies' => $this->companies()]);
    }

    public function update(Request $request, JobGrade $jobGrade): RedirectResponse
    {
        $this->authorize('organization.manage');

        $jobGrade->update($this->validated($request, $jobGrade));

        return redirect()->route('admin.organization.job-grades.index')->with('status', 'Job grade updated.');
    }

    public function destroy(JobGrade $jobGrade): RedirectResponse
    {
        $this->authorize('organization.manage');

        if ($jobGrade->positions()->exists()) {
            return back()->withErrors(['jobGrade' => 'Reassign the positions using this job grade before deleting it.']);
        }

        $jobGrade->delete();

        return redirect()->route('admin.organization.job-grades.index')->with('status', 'Job grade deleted.');
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies(): Collection
    {
        return Company::orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?JobGrade $jobGrade = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('job_grades', 'code')->where('company_id', $request->input('company_id'))->ignore($jobGrade?->id),
            ],
            'rank' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $jobGrade === null);

        return $validated;
    }
}
