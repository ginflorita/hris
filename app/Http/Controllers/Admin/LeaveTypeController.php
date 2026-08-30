<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('leave.view');

        return view('admin.leave.types.index', [
            'leaveTypes' => LeaveType::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('leave.create');

        return view('admin.leave.types.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('leave.create');

        LeaveType::create($this->validated($request));

        return redirect()->route('admin.leave.types.index')->with('status', 'Leave type created.');
    }

    public function edit(LeaveType $type): View
    {
        $this->authorize('leave.create');

        return view('admin.leave.types.edit', ['leaveType' => $type, 'companies' => $this->companies()]);
    }

    public function update(Request $request, LeaveType $type): RedirectResponse
    {
        $this->authorize('leave.create');

        $type->update($this->validated($request, $type));

        return redirect()->route('admin.leave.types.index')->with('status', 'Leave type updated.');
    }

    public function destroy(LeaveType $type): RedirectResponse
    {
        $this->authorize('leave.create');

        if ($type->leaveRequests()->exists() || $type->policies()->exists()) {
            return back()->withErrors(['leaveType' => 'Remove the requests and policies using this leave type before deleting it.']);
        }

        $type->delete();

        return redirect()->route('admin.leave.types.index')->with('status', 'Leave type deleted.');
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
    private function validated(Request $request, ?LeaveType $type = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('leave_types', 'code')->where('company_id', $request->input('company_id'))->ignore($type?->id),
            ],
            'max_days_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $validated['is_paid'] = $request->boolean('is_paid', $type === null);
        $validated['requires_approval'] = $request->boolean('requires_approval', $type === null);
        $validated['is_active'] = $request->boolean('is_active', $type === null);

        return $validated;
    }
}
