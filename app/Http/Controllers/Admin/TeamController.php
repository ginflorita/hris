<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.teams.index', [
            'teams' => Team::with(['company', 'department', 'section'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.teams.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Team::create($this->validated($request));

        return redirect()->route('admin.organization.teams.index')->with('status', 'Team created.');
    }

    public function edit(Team $team): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.teams.edit', ['team' => $team, ...$this->formData()]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('organization.manage');

        $team->update($this->validated($request, $team));

        return redirect()->route('admin.organization.teams.index')->with('status', 'Team updated.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('organization.manage');

        $team->delete();

        return redirect()->route('admin.organization.teams.index')->with('status', 'Team deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, departments: Collection<int, Department>, sections: Collection<int, Section>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::with('company')->orderBy('name')->get(),
            'sections' => Section::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Team $team = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $request->input('company_id')),
            ],
            'section_id' => [
                'nullable',
                Rule::exists('sections', 'id')->where('company_id', $request->input('company_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('teams', 'code')->where('company_id', $request->input('company_id'))->ignore($team?->id),
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $team === null);

        return $validated;
    }
}
