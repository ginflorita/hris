@extends('layouts.admin')

@section('title', 'Leave Requests')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Requests']])

@section('content')
    <x-admin.leave-subnav active="requests" />

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="employee_id" class="form-select" onchange="this.form.submit()">
                <option value="">All employees</option>
                @foreach ($employees as $employeeOption)
                    <option value="{{ $employeeOption->id }}" {{ (int) ($filters['employee_id'] ?? null) === $employeeOption->id ? 'selected' : '' }}>
                        {{ $employeeOption->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Enums\LeaveRequestStatus::cases() as $case)
                    <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                        {{ ucfirst($case->value) }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <x-admin.resource-index
        :create-url="auth()->user()->can('leave.create') ? route('admin.leave.requests.create') : null"
        create-label="Submit request"
    >
        <thead>
            <tr>
                <th>Employee</th>
                <th>Type</th>
                <th>Dates</th>
                <th>Days</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaveRequests as $leaveRequest)
                <tr>
                    <td>{{ $leaveRequest->employee->full_name }}</td>
                    <td>{{ $leaveRequest->leaveType->name }}</td>
                    <td>{{ $leaveRequest->start_date->format('M d') }} – {{ $leaveRequest->end_date->format('M d, Y') }}</td>
                    <td>{{ $leaveRequest->days_count }}</td>
                    <td>
                        @if ($leaveRequest->status->value === 'approved')
                            <span class="badge text-bg-success">Approved</span>
                        @elseif ($leaveRequest->status->value === 'rejected')
                            <span class="badge text-bg-danger">Rejected</span>
                        @elseif ($leaveRequest->status->value === 'cancelled')
                            <span class="badge text-bg-secondary">Cancelled</span>
                        @else
                            <span class="badge text-bg-warning">Pending</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if ($leaveRequest->status->value === 'pending')
                            @can('leave.approve')
                                <form method="POST" action="{{ route('admin.leave.requests.approve', $leaveRequest) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                </form>
                            @endcan
                            @can('leave.reject')
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leaveRequest->id }}">Reject</button>
                                <div class="modal fade" id="rejectModal{{ $leaveRequest->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.leave.requests.reject', $leaveRequest) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject leave request</h5>
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
                            @endcan
                        @endif
                        @if (in_array($leaveRequest->status->value, ['pending', 'approved']))
                            @can('leave.create')
                                <form method="POST" action="{{ route('admin.leave.requests.cancel', $leaveRequest) }}" class="d-inline"
                                      onsubmit="return confirm('Cancel this leave request?');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No leave requests yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $leaveRequests->links() }}</div>
@endsection
