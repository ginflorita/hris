@extends('layouts.admin')

@section('title', 'Session Roster')

@php($breadcrumbs = [
    ['label' => 'Training'],
    ['label' => 'Courses', 'url' => route('admin.training.courses.index')],
    ['label' => $course->name, 'url' => route('admin.training.courses.show', $course)],
    ['label' => $session->start_date->format('M d, Y')],
])

@section('content')
    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-1">{{ $course->name }}</h5>
            <div class="text-body-secondary">
                {{ $session->start_date->format('M d, Y') }} &ndash; {{ $session->end_date->format('M d, Y') }}
                @if ($session->location) &middot; {{ $session->location }} @endif
                @if ($session->capacity) &middot; {{ $session->occupiedSeats() }}/{{ $session->capacity }} seats filled @endif
            </div>
        </div>
    </div>

    <h6>Roster</h6>

    @can('training.manage')
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal">Enroll Employee</button>
        </div>
    @endcan

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Status</th>
                        <th>Certificate</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->employee->full_name }}</td>
                            <td>
                                @if ($enrollment->status->value === 'completed')
                                    <span class="badge text-bg-success">Completed</span>
                                @elseif ($enrollment->status->value === 'cancelled')
                                    <span class="badge text-bg-secondary">Cancelled</span>
                                @elseif ($enrollment->status->value === 'no_show')
                                    <span class="badge text-bg-warning">No Show</span>
                                @else
                                    <span class="badge text-bg-primary">Enrolled</span>
                                @endif
                            </td>
                            <td>
                                @if ($enrollment->certificate_number)
                                    {{ $enrollment->certificate_number }}
                                    @if ($enrollment->certificate_expires_at)
                                        <div class="text-body-secondary small">Expires {{ $enrollment->certificate_expires_at->format('M d, Y') }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                @can('training.manage')
                                    @if ($enrollment->status->value === 'enrolled')
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#decideEnrollmentModal{{ $enrollment->id }}">Record Outcome</button>
                                        <form method="POST" action="{{ route('admin.training.courses.sessions.enrollments.destroy', [$course, $session, $enrollment]) }}" class="d-inline"
                                              onsubmit="return confirm('Remove this enrollment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-3">No one enrolled yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('training.manage')
        <div class="modal fade" id="addEnrollmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.training.courses.sessions.enrollments.store', [$course, $session]) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Enroll Employee</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="employee_id">Employee</label>
                                <select id="employee_id" name="employee_id" class="form-select" required>
                                    <option value="">Select an employee</option>
                                    @foreach ($companyEmployees as $companyEmployee)
                                        <option value="{{ $companyEmployee->id }}">{{ $companyEmployee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Enroll</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($enrollments as $enrollment)
            @if ($enrollment->status->value === 'enrolled')
                <div class="modal fade" id="decideEnrollmentModal{{ $enrollment->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.training.courses.sessions.enrollments.update', [$course, $session, $enrollment]) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Record Outcome &mdash; {{ $enrollment->employee->full_name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label" for="status_{{ $enrollment->id }}">Outcome</label>
                                        <select id="status_{{ $enrollment->id }}" name="status" class="form-select" required>
                                            <option value="completed">Completed</option>
                                            <option value="no_show">No Show</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="certificate_number_{{ $enrollment->id }}">Certificate Number (if completed)</label>
                                        <input type="text" id="certificate_number_{{ $enrollment->id }}" name="certificate_number" class="form-control">
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="form-label" for="certificate_issued_at_{{ $enrollment->id }}">Issued On</label>
                                            <input type="date" id="certificate_issued_at_{{ $enrollment->id }}" name="certificate_issued_at" class="form-control">
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label" for="certificate_expires_at_{{ $enrollment->id }}">Expires On</label>
                                            <input type="date" id="certificate_expires_at_{{ $enrollment->id }}" name="certificate_expires_at" class="form-control">
                                        </div>
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
            @endif
        @endforeach
    @endcan
@endsection
