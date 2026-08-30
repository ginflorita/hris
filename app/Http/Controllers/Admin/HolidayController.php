<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HolidayType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $this->authorize('attendance.view');

        return view('admin.attendance.holidays.index', [
            'holidays' => Holiday::with('company')->orderBy('date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.holidays.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('attendance.manage');

        Holiday::create($this->validated($request));

        return redirect()->route('admin.attendance.holidays.index')->with('status', 'Holiday created.');
    }

    public function edit(Holiday $holiday): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.holidays.edit', ['holiday' => $holiday, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $holiday->update($this->validated($request, $holiday));

        return redirect()->route('admin.attendance.holidays.index')->with('status', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $holiday->delete();

        return redirect()->route('admin.attendance.holidays.index')->with('status', 'Holiday deleted.');
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
    private function validated(Request $request, ?Holiday $holiday = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'date' => [
                'required', 'date',
                Rule::unique('holidays', 'date')->where('company_id', $request->input('company_id'))->ignore($holiday?->id),
            ],
            'type' => ['required', Rule::enum(HolidayType::class)],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $holiday === null);

        return $validated;
    }
}
