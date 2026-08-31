@extends('layouts.portal')

@section('title', 'My Attendance')

@php($breadcrumbs = [['label' => 'My Attendance']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <h1 class="h4 mb-3">My Attendance</h1>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card mb-3">
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time in</th>
                            <th>Time out</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->attendances as $attendance)
                            @php($pendingRequest = $employee->attendanceCorrectionRequests->first(fn ($r) => $r->attendance_id === $attendance->id && $r->status->value === 'pending'))
                            <tr>
                                <td>{{ $attendance->date->format('M d, Y') }}</td>
                                <td>{{ $attendance->time_in?->format('g:i A') ?? '—' }}</td>
                                <td>{{ $attendance->time_out?->format('g:i A') ?? '—' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $attendance->status->value)) }}</td>
                                <td class="text-end">
                                    @if ($pendingRequest)
                                        <span class="badge text-bg-warning">Correction pending</span>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#correctModal{{ $attendance->id }}">
                                            Request correction
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-body-secondary py-3">No attendance records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($employee->attendanceCorrectionRequests->isNotEmpty())
            <div class="card">
                <div class="card-header">My Correction Requests</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employee->attendanceCorrectionRequests as $request)
                                <tr>
                                    <td>{{ $request->attendance->date->format('M d, Y') }}</td>
                                    <td>{{ $request->requested_time_in ?? '—' }} &ndash; {{ $request->requested_time_out ?? '—' }}</td>
                                    <td>
                                        @if ($request->status->value === 'approved')
                                            <span class="badge text-bg-success">Approved</span>
                                        @elseif ($request->status->value === 'rejected')
                                            <span class="badge text-bg-danger">Rejected</span>
                                            @if ($request->rejection_reason)
                                                <div class="text-body-secondary small">{{ $request->rejection_reason }}</div>
                                            @endif
                                        @else
                                            <span class="badge text-bg-warning">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @foreach ($employee->attendances as $attendance)
            <div class="modal fade" id="correctModal{{ $attendance->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('portal.attendance.correction-requests.store', $attendance) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Request correction &mdash; {{ $attendance->date->format('M d, Y') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Time in should be</label>
                                        <input type="time" name="requested_time_in" class="form-control" value="{{ $attendance->time_in?->format('H:i') }}">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Time out should be</label>
                                        <input type="time" name="requested_time_out" class="form-control" value="{{ $attendance->time_out?->format('H:i') }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status should be</label>
                                    <select name="requested_status" class="form-select" required>
                                        @foreach (\App\Enums\AttendanceStatus::cases() as $case)
                                            <option value="{{ $case->value }}" {{ $attendance->status->value === $case->value ? 'selected' : '' }}>
                                                {{ ucwords(str_replace('_', ' ', $case->value)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea name="reason" rows="3" class="form-control" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endunless
@endsection
