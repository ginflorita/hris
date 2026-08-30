<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        $this->authorize('attendance.view');

        return view('admin.attendance.shifts.index', [
            'shifts' => Shift::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.shifts.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('attendance.manage');

        Shift::create($this->validated($request));

        return redirect()->route('admin.attendance.shifts.index')->with('status', 'Shift created.');
    }

    public function edit(Shift $shift): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.shifts.edit', ['shift' => $shift, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $shift->update($this->validated($request, $shift));

        return redirect()->route('admin.attendance.shifts.index')->with('status', 'Shift updated.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $this->authorize('attendance.manage');

        if ($shift->schedules()->exists()) {
            return back()->withErrors(['shift' => 'Reassign the schedules using this shift before deleting it.']);
        }

        $shift->delete();

        return redirect()->route('admin.attendance.shifts.index')->with('status', 'Shift deleted.');
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
    private function validated(Request $request, ?Shift $shift = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('shifts', 'code')->where('company_id', $request->input('company_id'))->ignore($shift?->id),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i', 'after:break_start'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        $validated['is_night_shift'] = $request->boolean('is_night_shift');
        $validated['is_active'] = $request->boolean('is_active', $shift === null);

        return $validated;
    }
}
