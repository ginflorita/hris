@extends('layouts.admin')

@section('title', 'Applications')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Applications']])

@section('content')
    <x-admin.recruitment-subnav active="applications" />

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ApplicationStatus::cases() as $case)
                    <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                        {{ $case->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="job_posting_id" class="form-select" onchange="this.form.submit()">
                <option value="">All postings</option>
                @foreach ($postings as $posting)
                    <option value="{{ $posting->id }}" {{ (string) ($filters['job_posting_id'] ?? '') === (string) $posting->id ? 'selected' : '' }}>
                        {{ $posting->title }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Posting</th>
                        <th>Company</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>{{ $application->applicant->full_name }}</td>
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
                                <a href="{{ route('admin.recruitment.applicants.show', $application->applicant) }}" class="btn btn-sm btn-outline-secondary">View Applicant</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-3">No applications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $applications->links() }}</div>
@endsection
