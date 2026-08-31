@extends('layouts.admin')

@section('title', $application->applicant->full_name.' — '.$application->jobPosting->title)

@php($breadcrumbs = [
    ['label' => 'Recruitment'],
    ['label' => 'Applications', 'url' => route('admin.recruitment.applications.index')],
    ['label' => $application->applicant->full_name],
])

@section('content')
    <x-admin.recruitment-subnav active="applications" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">
                <a href="{{ route('admin.recruitment.applicants.show', $application->applicant) }}">{{ $application->applicant->full_name }}</a>
                &rarr; {{ $application->jobPosting->title }}
            </h1>
            <div class="text-body-secondary">
                {{ $application->jobPosting->company->name }} &middot; Applied {{ $application->applied_at->format('M d, Y') }}
            </div>
        </div>
        <div>
            @if ($application->status->value === 'hired')
                <span class="badge text-bg-success fs-6">{{ $application->status->label() }}</span>
            @elseif (in_array($application->status->value, ['rejected', 'withdrawn']))
                <span class="badge text-bg-danger fs-6">{{ $application->status->label() }}</span>
            @else
                <span class="badge text-bg-warning fs-6">{{ $application->status->label() }}</span>
            @endif
        </div>
    </div>

    @if ($application->rejection_reason)
        <div class="alert alert-secondary">Rejected: {{ $application->rejection_reason }}</div>
    @endif

    @can('recruitment.manage')
        @unless ($application->status->isTerminal())
            <div class="card mb-3">
                <div class="card-body d-flex align-items-center gap-2">
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
                        <button type="submit" class="btn btn-sm btn-outline-success">Update Status</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                </div>
            </div>

            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
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

    <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
        <h2 class="h6 mb-0">Interviews</h2>
        @can('recruitment.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newInterviewModal">Schedule Interview</button>
        @endcan
    </div>
    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Scheduled</th>
                        <th>Interviewer</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th>Recommendation</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($application->interviews as $interview)
                        <tr>
                            <td>{{ $interview->type->label() }}</td>
                            <td>{{ $interview->scheduled_at->format('M d, Y g:i A') }}</td>
                            <td>{{ $interview->interviewer?->full_name ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $interview->status->value)) }}</td>
                            <td>{{ $interview->rating ?? '—' }}</td>
                            <td>{{ $interview->recommendation?->label() ?? '—' }}</td>
                            <td class="text-end">
                                @can('recruitment.manage')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInterviewModal{{ $interview->id }}">Edit</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-3">No interviews scheduled yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 mb-0">Assessments</h2>
        @can('recruitment.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAssessmentModal">Assign Assessment</button>
        @endcan
    </div>
    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Due</th>
                        <th>Completed</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($application->assessments as $assessment)
                        <tr>
                            <td>{{ $assessment->type->label() }}</td>
                            <td>{{ $assessment->due_at?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $assessment->completed_at?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $assessment->score ?? '—' }}</td>
                            <td>
                                @if ($assessment->passed === true)
                                    <span class="badge text-bg-success">Passed</span>
                                @elseif ($assessment->passed === false)
                                    <span class="badge text-bg-danger">Failed</span>
                                @else
                                    <span class="badge text-bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('recruitment.manage')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAssessmentModal{{ $assessment->id }}">Edit</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-3">No assessments assigned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('recruitment.manage')
        <div class="modal fade" id="newInterviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.recruitment.applications.interviews.store', $application) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Schedule Interview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" required>
                                    @foreach (\App\Enums\InterviewType::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Interviewer</label>
                                <select name="interviewer_id" class="form-select">
                                    <option value="">Unassigned</option>
                                    @foreach ($interviewers as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Scheduled At</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" placeholder="Room, or video call link">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($application->interviews as $interview)
            <div class="modal fade" id="editInterviewModal{{ $interview->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.recruitment.applications.interviews.update', [$application, $interview]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Update Interview — {{ $interview->type->label() }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Scheduled At</label>
                                    <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ $interview->scheduled_at->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="location" class="form-control" value="{{ $interview->location }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        @foreach (\App\Enums\InterviewStatus::cases() as $case)
                                            <option value="{{ $case->value }}" {{ $interview->status->value === $case->value ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $case->value)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Rating (1-5)</label>
                                        <input type="number" name="rating" min="1" max="5" class="form-control" value="{{ $interview->rating }}">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Recommendation</label>
                                        <select name="recommendation" class="form-select">
                                            <option value="">—</option>
                                            @foreach (\App\Enums\InterviewRecommendation::cases() as $case)
                                                <option value="{{ $case->value }}" {{ $interview->recommendation?->value === $case->value ? 'selected' : '' }}>
                                                    {{ $case->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Feedback</label>
                                    <textarea name="feedback" rows="3" class="form-control">{{ $interview->feedback }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="modal fade" id="newAssessmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.recruitment.applications.assessments.store', $application) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Assessment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" required>
                                    @foreach (\App\Enums\AssessmentType::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_at" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Assign</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($application->assessments as $assessment)
            <div class="modal fade" id="editAssessmentModal{{ $assessment->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.recruitment.applications.assessments.update', [$application, $assessment]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Update Assessment — {{ $assessment->type->label() }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Completed At</label>
                                    <input type="date" name="completed_at" class="form-control" value="{{ $assessment->completed_at?->format('Y-m-d') }}">
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Score</label>
                                        <input type="number" step="0.01" name="score" class="form-control" value="{{ $assessment->score }}">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Result</label>
                                        <select name="passed" class="form-select">
                                            <option value="" {{ is_null($assessment->passed) ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $assessment->passed === true ? 'selected' : '' }}>Passed</option>
                                            <option value="0" {{ $assessment->passed === false ? 'selected' : '' }}>Failed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" rows="3" class="form-control">{{ $assessment->notes }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan
@endsection
