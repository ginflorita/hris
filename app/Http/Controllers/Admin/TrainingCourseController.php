<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TrainingCourse;
use App\Models\TrainingProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sessions are managed from a course's own show() page via add/edit
 * modals -- the same pattern ContributionRateTable uses for its
 * brackets -- so this controller also has show(), unlike the plain
 * index/create/edit shape most other lookup controllers use.
 */
class TrainingCourseController extends Controller
{
    public function index(): View
    {
        $this->authorize('training.view');

        return view('admin.training.courses.index', [
            'courses' => TrainingCourse::with(['company', 'provider'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('admin.training.courses.create', ['companies' => $this->companies(), 'providers' => TrainingProvider::with('company')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('training.manage');

        $course = TrainingCourse::create($this->validated($request));

        return redirect()->route('admin.training.courses.show', $course)->with('status', 'Training course created.');
    }

    public function show(TrainingCourse $course): View
    {
        $this->authorize('training.view');

        return view('admin.training.courses.show', [
            'course' => $course->load('provider'),
            'sessions' => $course->sessions()->orderByDesc('start_date')->get(),
        ]);
    }

    public function edit(TrainingCourse $course): View
    {
        $this->authorize('training.manage');

        return view('admin.training.courses.edit', [
            'course' => $course,
            'companies' => $this->companies(),
            'providers' => TrainingProvider::with('company')->where('company_id', $course->company_id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, TrainingCourse $course): RedirectResponse
    {
        $this->authorize('training.manage');

        $course->update($this->validated($request, $course));

        return redirect()->route('admin.training.courses.show', $course)->with('status', 'Training course updated.');
    }

    public function destroy(TrainingCourse $course): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($course->sessions()->exists()) {
            return back()->withErrors(['course' => 'Remove the sessions using this course before deleting it.']);
        }

        $course->delete();

        return redirect()->route('admin.training.courses.index')->with('status', 'Training course deleted.');
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
    private function validated(Request $request, ?TrainingCourse $course = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'training_provider_id' => ['nullable', Rule::exists('training_providers', 'id')->where('company_id', $request->input('company_id'))],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('training_courses', 'name')->where('company_id', $request->input('company_id'))->ignore($course?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $course === null);

        return $validated;
    }
}
