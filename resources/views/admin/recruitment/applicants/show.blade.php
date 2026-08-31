@extends('layouts.admin')

@section('title', $applicant->full_name)

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Applicants', 'url' => route('admin.recruitment.applicants.index')], ['label' => $applicant->full_name]])

@section('content')
    <x-admin.recruitment-subnav active="applicants" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $applicant->full_name }}</h1>
            <div class="text-body-secondary">
                {{ $applicant->email }} @if ($applicant->phone) &middot; {{ $applicant->phone }} @endif
                &middot; {{ $applicant->source->label() }}
            </div>
        </div>
        @can('recruitment.manage')
            <div>
                <a href="{{ route('admin.recruitment.applicants.edit', $applicant) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newApplicationModal">New Application</button>
            </div>
        @endcan
    </div>

    @if ($applicant->resume_path)
        <p><a href="{{ route('admin.recruitment.applicants.resume', $applicant) }}">Download resume ({{ $applicant->resume_original_filename }})</a></p>
    @endif

    @if ($applicant->notes)
        <div class="card mb-3">
            <div class="card-header">Notes</div>
            <div class="card-body">{{ $applicant->notes }}</div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Applications</div>
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Posting</th>
                        <th>Company</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applicant->applications as $application)
                        <tr>
                            <td>{{ $application->jobPosting->title }}</td>
                            <td>{{ $application->jobPosting->company->name }}</td>
                            <td>{{ $application->applied_at->format('M d, Y') }}</td>
                            <td>
                                @if ($application->status->value === 'hired')
                                    <span class="badge text-bg-success">{{ $application->status->label() }}</span>
                                @elseif (in_array($application->status->value, ['rejected', 'withdrawn']))
                                    <span class="badge text-bg-danger">{{ $application->status->label() }}</span>
                                @else
                                    <span class="badge text-bg-warning">{{ $application->status->label() }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('recruitment.manage')
                                    @unless ($application->status->isTerminal())
                                        <form method="POST" action="{{ route('admin.recruitment.applications.status', $application) }}" class="d-inline-flex gap-1">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                @foreach (\App\Enums\ApplicationStatus::cases() as $case)
                                                    @continue($case->value === 'rejected')
                                                    <option value="{{ $case->value }}" {{ $application->status->value === $case->value ? 'selected' : '' }}>
                                                        {{ $case->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-success">Update</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $application->id }}">Reject</button>

                                        <div class="modal fade" id="rejectModal{{ $application->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('admin.recruitment.applications.status', $application) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="rejected">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject application</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <label class="form-label">Reason</label>
                                                            <textarea name="rejection_reason" rows="2" class="form-control" required></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-3">No applications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="newApplicationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.recruitment.applicants.applications.store', $applicant) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Apply {{ $applicant->full_name }} to a posting</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Job Posting</label>
                        <select name="job_posting_id" class="form-select" required>
                            <option value="">Select a published posting</option>
                            @foreach ($publishedPostings as $posting)
                                <option value="{{ $posting->id }}">{{ $posting->title }} ({{ $posting->company->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
