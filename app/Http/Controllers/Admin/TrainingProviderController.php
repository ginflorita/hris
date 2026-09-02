<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TrainingProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingProviderController extends Controller
{
    public function index(): View
    {
        $this->authorize('training.view');

        return view('admin.training.providers.index', [
            'providers' => TrainingProvider::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('admin.training.providers.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('training.manage');

        TrainingProvider::create($this->validated($request));

        return redirect()->route('admin.training.providers.index')->with('status', 'Training provider created.');
    }

    public function edit(TrainingProvider $provider): View
    {
        $this->authorize('training.manage');

        return view('admin.training.providers.edit', ['provider' => $provider, 'companies' => $this->companies()]);
    }

    public function update(Request $request, TrainingProvider $provider): RedirectResponse
    {
        $this->authorize('training.manage');

        $provider->update($this->validated($request, $provider));

        return redirect()->route('admin.training.providers.index')->with('status', 'Training provider updated.');
    }

    public function destroy(TrainingProvider $provider): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($provider->courses()->exists()) {
            return back()->withErrors(['provider' => 'Remove the courses using this provider before deleting it.']);
        }

        $provider->delete();

        return redirect()->route('admin.training.providers.index')->with('status', 'Training provider deleted.');
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
    private function validated(Request $request, ?TrainingProvider $provider = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('training_providers', 'name')->where('company_id', $request->input('company_id'))->ignore($provider?->id),
            ],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $provider === null);

        return $validated;
    }
}
