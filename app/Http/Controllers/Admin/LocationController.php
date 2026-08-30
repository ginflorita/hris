<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.locations.index', [
            'locations' => Location::with(['company', 'branch'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.locations.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Location::create($this->validated($request));

        return redirect()->route('admin.organization.locations.index')->with('status', 'Location created.');
    }

    public function edit(Location $location): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.locations.edit', ['location' => $location, ...$this->formData()]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $this->authorize('organization.manage');

        $location->update($this->validated($request, $location));

        return redirect()->route('admin.organization.locations.index')->with('status', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('organization.manage');

        $location->delete();

        return redirect()->route('admin.organization.locations.index')->with('status', 'Location deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, branches: Collection<int, Branch>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Location $location = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('company_id', $request->input('company_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('locations', 'code')->where('company_id', $request->input('company_id'))->ignore($location?->id),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $location === null);

        return $validated;
    }
}
