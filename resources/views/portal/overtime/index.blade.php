@extends('layouts.portal')

@section('title', 'My Overtime')

@php($breadcrumbs = [['label' => 'My Overtime']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h1 class="h4 mb-0">My Overtime</h1>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestOvertimeModal">Request overtime</button>
        </div>

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
                            <th>Hours</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->overtimeRequests as $overtimeRequest)
                            <tr>
                                <td>{{ $overtimeRequest->date->format('M d, Y') }}</td>
                                <td>{{ number_format($overtimeRequest->requested_hours, 2) }}</td>
                                <td>{{ $overtimeRequest->reason }}</td>
                                <td>
                                    @php($badgeClass = match ($overtimeRequest->status) {
                                        \App\Enums\OvertimeStatus::Pending => 'text-bg-warning',
                                        \App\Enums\OvertimeStatus::Approved => 'text-bg-success',
                                        \App\Enums\OvertimeStatus::Rejected => 'text-bg-danger',
                                    })
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($overtimeRequest->status->value) }}</span>
                                    @if ($overtimeRequest->status === \App\Enums\OvertimeStatus::Rejected && $overtimeRequest->rejection_reason)
                                        <div class="text-body-secondary small">{{ $overtimeRequest->rejection_reason }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-secondary py-3">No overtime requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="requestOvertimeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('portal.overtime.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Request overtime</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hours</label>
                                <input type="number" step="0.5" min="0.5" max="24" name="requested_hours" class="form-control" required>
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
    @endunless
@endsection
