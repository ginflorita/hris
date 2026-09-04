@extends('layouts.admin')

@section('title', 'Review Request')

@php($breadcrumbs = [['label' => 'My Approvals', 'url' => route('admin.workflow.instances.index')], ['label' => 'Review Request']])

@section('content')
    @php($statusBadge = match ($workflowInstance->status) {
        \App\Enums\WorkflowInstanceStatus::InProgress => 'text-bg-warning',
        \App\Enums\WorkflowInstanceStatus::Approved => 'text-bg-success',
        \App\Enums\WorkflowInstanceStatus::Rejected => 'text-bg-danger',
        \App\Enums\WorkflowInstanceStatus::Cancelled => 'text-bg-secondary',
    })

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h1 class="h5 mb-1">{{ $workflowInstance->workflowDefinition->name }}</h1>
                    <div class="text-body-secondary small">
                        Requested by {{ $workflowInstance->initiatedBy?->name ?? 'Unknown' }}
                        on {{ $workflowInstance->created_at->format('M d, Y') }}
                    </div>
                </div>
                <span class="badge {{ $statusBadge }}">{{ ucwords(str_replace('_', ' ', $workflowInstance->status->value)) }}</span>
            </div>

            @if ($workflowInstance->subject instanceof \App\Models\EmployeeInformationChangeRequest)
                @include('admin.workflow.instances._employee-information-change-subject', ['request' => $workflowInstance->subject])
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Steps</div>
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Step</th>
                        <th>Status</th>
                        <th>Decided by</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($workflowInstance->instanceSteps as $step)
                        <tr class="{{ $currentStep?->id === $step->id ? 'table-active' : '' }}">
                            <td>{{ $step->step_order }}</td>
                            <td>{{ $step->name }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $step->status->value)) }}</td>
                            <td>{{ $step->actedBy?->name ?? '—' }}</td>
                            <td>{{ $step->comments ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($canAct)
        <div class="card">
            <div class="card-body">
                <h2 class="h6">Your decision</h2>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('admin.workflow.instances.approve', $workflowInstance) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.workflow.instances.reject', $workflowInstance) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Reject request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Reason</label>
                            <textarea name="comments" rows="3" class="form-control" required></textarea>
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
@endsection
