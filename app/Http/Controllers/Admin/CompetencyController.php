<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Competency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Company-scoped lookup, same shape and permission-reuse pattern as
 * LeaveType/Holiday -- gated by training.view/training.manage since
 * blueprint §23 (Training and Learning) is Competencies/Skills' more
 * natural long-term owner, and Training already has its own seeded
 * permission group (unlike Compensation, which had to borrow one).
 */
class CompetencyController extends Controller
{
    public function index(): View
    {
        $this->authorize('training.view');

        return view('admin.training.competencies.index', [
            'competencies' => Competency::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('admin.training.competencies.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('training.manage');

        Competency::create($this->validated($request));

        return redirect()->route('admin.training.competencies.index')->with('status', 'Competency created.');
    }

    public function edit(Competency $competency): View
    {
        $this->authorize('training.manage');

        return view('admin.training.competencies.edit', ['competency' => $competency, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Competency $competency): RedirectResponse
    {
        $this->authorize('training.manage');

        $competency->update($this->validated($request, $competency));

        return redirect()->route('admin.training.competencies.index')->with('status', 'Competency updated.');
    }

    public function destroy(Competency $competency): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($competency->employeeCompetencies()->exists()) {
            return back()->withErrors(['competency' => 'Remove the employee ratings using this competency before deleting it.']);
        }

        $competency->delete();

        return redirect()->route('admin.training.competencies.index')->with('status', 'Competency deleted.');
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
    private function validated(Request $request, ?Competency $competency = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('competencies', 'name')->where('company_id', $request->input('company_id'))->ignore($competency?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $competency === null);

        return $validated;
    }
}
