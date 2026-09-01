<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerformanceCycleStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PerformanceCycle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Company-scoped CRUD, same shape as LeaveType/Holiday/PayrollGroup.
 * Status moves only through activate()/close() (Draft -> Active ->
 * Closed), not a freely-editable form field -- same lifecycle-guard
 * pattern JobPosting's publish()/close() use, rather than PayrollPeriod's
 * guarded controller actions reinvented per module.
 */
class PerformanceCycleController extends Controller
{
    public function index(): View
    {
        $this->authorize('performance.view');

        return view('admin.performance.cycles.index', [
            'cycles' => PerformanceCycle::with('company')->withCount('goals')->orderByDesc('start_date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('performance.manage');

        return view('admin.performance.cycles.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('performance.manage');

        PerformanceCycle::create([...$this->validated($request), 'status' => PerformanceCycleStatus::Draft]);

        return redirect()->route('admin.performance.cycles.index')->with('status', 'Performance cycle created.');
    }

    public function edit(PerformanceCycle $cycle): View
    {
        $this->authorize('performance.manage');

        return view('admin.performance.cycles.edit', ['cycle' => $cycle, 'companies' => $this->companies()]);
    }

    public function update(Request $request, PerformanceCycle $cycle): RedirectResponse
    {
        $this->authorize('performance.manage');

        $cycle->update($this->validated($request));

        return redirect()->route('admin.performance.cycles.index')->with('status', 'Performance cycle updated.');
    }

    public function activate(PerformanceCycle $cycle): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($cycle->status === PerformanceCycleStatus::Draft, 422, 'Only a draft cycle can be activated.');

        $cycle->update(['status' => PerformanceCycleStatus::Active]);

        return back()->with('status', 'Performance cycle activated.');
    }

    public function close(PerformanceCycle $cycle): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($cycle->status === PerformanceCycleStatus::Active, 422, 'Only an active cycle can be closed.');

        $cycle->update(['status' => PerformanceCycleStatus::Closed]);

        return back()->with('status', 'Performance cycle closed.');
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
    private function validated(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
