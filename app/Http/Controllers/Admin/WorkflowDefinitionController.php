<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkflowProcessType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class WorkflowDefinitionController extends Controller
{
    public function index(): View
    {
        $this->authorize('workflow.view');

        return view('admin.workflow.definitions.index', [
            'workflowDefinitions' => WorkflowDefinition::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('workflow.manage');

        return view('admin.workflow.definitions.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('workflow.manage');

        $definition = WorkflowDefinition::create($this->validated($request));

        return redirect()->route('admin.workflow.definitions.show', $definition)
            ->with('status', 'Workflow created. Add its steps below.');
    }

    public function show(WorkflowDefinition $workflowDefinition): View
    {
        $this->authorize('workflow.view');

        return view('admin.workflow.definitions.show', [
            'workflowDefinition' => $workflowDefinition->load('steps'),
            'groupedPermissions' => Permission::orderBy('name')->get()->groupBy(fn (Permission $permission) => Str::beforeLast($permission->name, '.')),
        ]);
    }

    public function edit(WorkflowDefinition $workflowDefinition): View
    {
        $this->authorize('workflow.manage');

        return view('admin.workflow.definitions.edit', [
            'workflowDefinition' => $workflowDefinition,
            'companies' => $this->companies(),
        ]);
    }

    public function update(Request $request, WorkflowDefinition $workflowDefinition): RedirectResponse
    {
        $this->authorize('workflow.manage');

        $workflowDefinition->update($this->validated($request, $workflowDefinition));

        return redirect()->route('admin.workflow.definitions.index')->with('status', 'Workflow updated.');
    }

    public function destroy(WorkflowDefinition $workflowDefinition): RedirectResponse
    {
        $this->authorize('workflow.manage');

        if ($workflowDefinition->instances()->exists()) {
            return back()->withErrors(['workflowDefinition' => 'This workflow has requests that have used it and can\'t be deleted. Mark it inactive instead.']);
        }

        $workflowDefinition->steps()->delete();
        $workflowDefinition->delete();

        return redirect()->route('admin.workflow.definitions.index')->with('status', 'Workflow deleted.');
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
    private function validated(Request $request, ?WorkflowDefinition $definition = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'process_type' => ['required', Rule::enum(WorkflowProcessType::class)],
            'description' => ['nullable', 'string'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $definition === null);

        return $validated;
    }
}
