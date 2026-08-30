<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.sections.index', [
            'sections' => Section::with(['company', 'department'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.sections.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Section::create($this->validated($request));

        return redirect()->route('admin.organization.sections.index')->with('status', 'Section created.');
    }

    public function edit(Section $section): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.sections.edit', ['section' => $section, ...$this->formData()]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('organization.manage');

        $section->update($this->validated($request, $section));

        return redirect()->route('admin.organization.sections.index')->with('status', 'Section updated.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $this->authorize('organization.manage');

        if ($section->teams()->exists()) {
            return back()->withErrors(['section' => 'Reassign or remove this section\'s teams before deleting it.']);
        }

        $section->delete();

        return redirect()->route('admin.organization.sections.index')->with('status', 'Section deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, departments: Collection<int, Department>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Section $section = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $request->input('company_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('sections', 'code')->where('company_id', $request->input('company_id'))->ignore($section?->id),
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $section === null);

        return $validated;
    }
}
