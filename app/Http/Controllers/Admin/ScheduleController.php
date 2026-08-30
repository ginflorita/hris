<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScheduleType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    private const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    public function index(): View
    {
        $this->authorize('attendance.view');

        return view('admin.attendance.schedules.index', [
            'schedules' => Schedule::with(['company', 'shift'])->orderBy('name')->paginate(20),
            'weekdays' => self::WEEKDAYS,
        ]);
    }

    public function create(): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.schedules.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('attendance.manage');

        Schedule::create($this->validated($request));

        return redirect()->route('admin.attendance.schedules.index')->with('status', 'Schedule created.');
    }

    public function edit(Schedule $schedule): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.schedules.edit', ['schedule' => $schedule, ...$this->formData()]);
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $schedule->update($this->validated($request, $schedule));

        return redirect()->route('admin.attendance.schedules.index')->with('status', 'Schedule updated.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('attendance.manage');

        if ($schedule->employeeSchedules()->exists()) {
            return back()->withErrors(['schedule' => 'Reassign the employees on this schedule before deleting it.']);
        }

        $schedule->delete();

        return redirect()->route('admin.attendance.schedules.index')->with('status', 'Schedule deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, shifts: Collection<int, Shift>, weekdays: list<string>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'shifts' => Shift::with('company')->orderBy('name')->get(),
            'weekdays' => self::WEEKDAYS,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Schedule $schedule = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('company_id', $request->input('company_id'))],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('schedules', 'code')->where('company_id', $request->input('company_id'))->ignore($schedule?->id),
            ],
            'type' => ['required', Rule::enum(ScheduleType::class)],
            'rest_days' => ['nullable', 'array'],
            'rest_days.*' => ['integer', 'min:0', 'max:6'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $schedule === null);

        return $validated;
    }
}
