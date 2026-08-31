@extends('layouts.portal')

@section('title', 'My Leave')

@php($breadcrumbs = [['label' => 'My Leave']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h1 class="h4 mb-0">My Leave</h1>
            <a href="{{ route('portal.leave.create') }}" class="btn btn-primary btn-sm">Request leave</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-header">Balances</div>
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Leave type</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->leaveBalances as $balance)
                            <tr>
                                <td>{{ $balance->leaveType->name }}</td>
                                <td class="text-end">{{ number_format($balance->balance, 1) }} day(s)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-body-secondary py-3">No leave balances on file.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">My Requests</div>
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Leave type</th>
                            <th>Dates</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->leaveRequests as $leaveRequest)
                            <tr>
                                <td>{{ $leaveRequest->leaveType->name }}</td>
                                <td>{{ $leaveRequest->start_date->format('M d, Y') }} &ndash; {{ $leaveRequest->end_date->format('M d, Y') }}</td>
                                <td>{{ $leaveRequest->days_count }}</td>
                                <td>
                                    @php($badgeClass = match ($leaveRequest->status) {
                                        \App\Enums\LeaveRequestStatus::Pending => 'text-bg-warning',
                                        \App\Enums\LeaveRequestStatus::Approved => 'text-bg-success',
                                        \App\Enums\LeaveRequestStatus::Rejected => 'text-bg-danger',
                                        \App\Enums\LeaveRequestStatus::Cancelled => 'text-bg-secondary',
                                    })
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($leaveRequest->status->value) }}</span>
                                    @if ($leaveRequest->status === \App\Enums\LeaveRequestStatus::Rejected && $leaveRequest->rejection_reason)
                                        <div class="text-body-secondary small">{{ $leaveRequest->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (in_array($leaveRequest->status, [\App\Enums\LeaveRequestStatus::Pending, \App\Enums\LeaveRequestStatus::Approved], true))
                                        <form method="POST" action="{{ route('portal.leave.cancel', $leaveRequest) }}" class="d-inline"
                                              onsubmit="return confirm('Cancel this leave request?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-body-secondary py-3">No leave requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless
@endsection
