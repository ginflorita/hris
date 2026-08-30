<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobLevelController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.job-levels.index', [
            'jobLevels' => JobLevel::with('company')->orderBy('company_id')->orderBy('rank')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.job-levels.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        JobLevel::create($this->validated($request));

        return redirect()->route('admin.organization.job-levels.index')->with('status', 'Job level created.');
    }

    public function edit(JobLevel $jobLevel): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.job-levels.edit', ['jobLevel' => $jobLevel, 'companies' => $this->companies()]);
    }

    public function update(Request $request, JobLevel $jobLevel): RedirectResponse
    {
        $this->authorize('organization.manage');

        $jobLevel->update($this->validated($request, $jobLevel));

        return redirect()->route('admin.organization.job-levels.index')->with('status', 'Job level updated.');
    }

    public function destroy(JobLevel $jobLevel): RedirectResponse
    {
        $this->authorize('organization.manage');

        if ($jobLevel->positions()->exists()) {
            return back()->withErrors(['jobLevel' => 'Reassign the positions using this job level before deleting it.']);
        }

        $jobLevel->delete();

        return redirect()->route('admin.organization.job-levels.index')->with('status', 'Job level deleted.');
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
    private function validated(Request $request, ?JobLevel $jobLevel = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('job_levels', 'code')->where('company_id', $request->input('company_id'))->ignore($jobLevel?->id),
            ],
            'rank' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $jobLevel === null);

        return $validated;
    }
}
