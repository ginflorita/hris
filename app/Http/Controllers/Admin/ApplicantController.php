<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicantSource;
use App\Enums\JobPostingStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A candidate-pool profile, not scoped to one company -- see
 * CLAUDE.md "Recruitment" for why applicants have no company_id.
 * The resume is a single file directly on the record (like Employee's
 * profile_photo_path), not a full sub-resource the way EmployeeDocument
 * is -- blueprint separates "Resume" from "applicant_documents" as two
 * different functions, and only the resume is in scope for this slice.
 */
class ApplicantController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('recruitment.view');

        $query = Applicant::withCount('applications')->orderByDesc('created_at');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('admin.recruitment.applicants.index', [
            'applicants' => $query->paginate(20)->withQueryString(),
            'q' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('recruitment.manage');

        return view('admin.recruitment.applicants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $applicant = Applicant::create([
            ...$this->validated($request),
            ...$this->uploadedResume($request),
        ]);

        return redirect()->route('admin.recruitment.applicants.show', $applicant)->with('status', 'Applicant added.');
    }

    public function show(Applicant $applicant): View
    {
        $this->authorize('recruitment.view');

        $applicant->load(['applications.jobPosting.company']);

        return view('admin.recruitment.applicants.show', [
            'applicant' => $applicant,
            'publishedPostings' => JobPosting::with('company')->where('status', JobPostingStatus::Published)->orderBy('title')->get(),
        ]);
    }

    public function edit(Applicant $applicant): View
    {
        $this->authorize('recruitment.manage');

        return view('admin.recruitment.applicants.edit', ['applicant' => $applicant]);
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $applicant->update([
            ...$this->validated($request),
            ...$this->uploadedResume($request, $applicant),
        ]);

        return redirect()->route('admin.recruitment.applicants.show', $applicant)->with('status', 'Applicant updated.');
    }

    public function downloadResume(Applicant $applicant): StreamedResponse
    {
        $this->authorize('recruitment.view');
        abort_unless($applicant->resume_path, 404);

        return Storage::disk('local')->download($applicant->resume_path, $applicant->resume_original_filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['required', Rule::enum(ApplicantSource::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadedResume(Request $request, ?Applicant $applicant = null): array
    {
        if (! $request->hasFile('resume')) {
            return [];
        }

        $request->validate(['resume' => ['file', 'max:10240', 'mimes:pdf,doc,docx']]);

        if ($applicant?->resume_path) {
            Storage::disk('local')->delete($applicant->resume_path);
        }

        $file = $request->file('resume');

        return [
            'resume_path' => $file->store('applicant-resumes', 'local'),
            'resume_original_filename' => $file->getClientOriginalName(),
        ];
    }
}
