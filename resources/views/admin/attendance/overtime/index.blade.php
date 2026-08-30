@extends('layouts.admin')

@section('title', 'Overtime')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Overtime']])

@section('content')
    <x-admin.attendance-subnav active="overtime" />

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Enums\OvertimeStatus::cases() as $case)
                    <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                        {{ ucfirst($case->value) }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <x-admin.resource-index
        :create-url="auth()->user()->can('attendance.manage') ? route('admin.attendance.overtime.create') : null"
        create-label="Request overtime"
        error-key="overtime"
    >
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Hours</th>
                <th>Reason</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($overtimeRequests as $overtimeRequest)
                <tr>
                    <td>{{ $overtimeRequest->date->format('M d, Y') }}</td>
                    <td>{{ $overtimeRequest->employee->full_name }}</td>
                    <td>{{ $overtimeRequest->requested_hours }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($overtimeRequest->reason, 40) }}</td>
                    <td>
                        @if ($overtimeRequest->status->value === 'approved')
                            <span class="badge text-bg-success">Approved</span>
                        @elseif ($overtimeRequest->status->value === 'rejected')
                            <span class="badge text-bg-danger">Rejected</span>
                        @else
                            <span class="badge text-bg-warning">Pending</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('attendance.manage')
                            @if ($overtimeRequest->status->value === 'pending')
                                <form method="POST" action="{{ route('admin.attendance.overtime.approve', $overtimeRequest) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $overtimeRequest->id }}">Reject</button>

                                <div class="modal fade" id="rejectModal{{ $overtimeRequest->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.attendance.overtime.reject', $overtimeRequest) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject overtime request</h5>
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
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No overtime requests yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $overtimeRequests->links() }}</div>
@endsection
