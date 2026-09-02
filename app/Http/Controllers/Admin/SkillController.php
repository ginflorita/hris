<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(): View
    {
        $this->authorize('training.view');

        return view('admin.training.skills.index', [
            'skills' => Skill::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('admin.training.skills.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('training.manage');

        Skill::create($this->validated($request));

        return redirect()->route('admin.training.skills.index')->with('status', 'Skill created.');
    }

    public function edit(Skill $skill): View
    {
        $this->authorize('training.manage');

        return view('admin.training.skills.edit', ['skill' => $skill, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $this->authorize('training.manage');

        $skill->update($this->validated($request, $skill));

        return redirect()->route('admin.training.skills.index')->with('status', 'Skill updated.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($skill->employeeSkills()->exists()) {
            return back()->withErrors(['skill' => 'Remove the employee ratings using this skill before deleting it.']);
        }

        $skill->delete();

        return redirect()->route('admin.training.skills.index')->with('status', 'Skill deleted.');
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
    private function validated(Request $request, ?Skill $skill = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('skills', 'name')->where('company_id', $request->input('company_id'))->ignore($skill?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $skill === null);

        return $validated;
    }
}
