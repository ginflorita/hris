@extends('layouts.admin')

@section('title', 'Correction Requests')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Correction Requests']])

@section('content')
    <x-admin.attendance-subnav active="correction-requests" />

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Enums\AttendanceCorrectionRequestStatus::cases() as $case)
                    <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                        {{ ucfirst($case->value) }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Requested</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($correctionRequests as $correctionRequest)
                        <tr>
                            <td>{{ $correctionRequest->attendance->date->format('M d, Y') }}</td>
                            <td>{{ $correctionRequest->employee->full_name }}</td>
                            <td>
                                {{ $correctionRequest->requested_time_in ?? '—' }} &ndash; {{ $correctionRequest->requested_time_out ?? '—' }}
                                ({{ ucwords(str_replace('_', ' ', $correctionRequest->requested_status->value)) }})
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($correctionRequest->reason, 40) }}</td>
                            <td>
                                @if ($correctionRequest->status->value === 'approved')
                                    <span class="badge text-bg-success">Approved</span>
                                @elseif ($correctionRequest->status->value === 'rejected')
                                    <span class="badge text-bg-danger">Rejected</span>
                                @else
                                    <span class="badge text-bg-warning">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($correctionRequest->status->value === 'pending')
                                    <form method="POST" action="{{ route('admin.attendance.correction-requests.approve', $correctionRequest) }}" class="d-inline"
                                          onsubmit="return confirm('Approve and apply this correction?');">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $correctionRequest->id }}">Reject</button>

                                    <div class="modal fade" id="rejectModal{{ $correctionRequest->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('admin.attendance.correction-requests.reject', $correctionRequest) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject correction request</h5>
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
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-3">No correction requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $correctionRequests->links() }}</div>
@endsection
