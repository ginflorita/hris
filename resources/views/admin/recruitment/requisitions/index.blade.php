@extends('layouts.admin')

@section('title', 'Job Requisitions')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Requisitions']])

@section('content')
    <x-admin.recruitment-subnav active="requisitions" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="row g-2">
            <div class="col-auto">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\JobRequisitionStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                            {{ ucfirst($case->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
        @can('recruitment.manage')
            <a href="{{ route('admin.recruitment.requisitions.create') }}" class="btn btn-primary btn-sm">New Requisition</a>
        @endcan
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
                        <th>Company</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Openings</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requisitions as $requisition)
                        <tr>
                            <td>{{ $requisition->company->name }}</td>
                            <td>{{ $requisition->department->name ?? '—' }}</td>
                            <td>{{ $requisition->position->title ?? '—' }}</td>
                            <td>{{ $requisition->openings_count }}</td>
                            <td>
                                @if ($requisition->status->value === 'approved')
                                    <span class="badge text-bg-success">Approved</span>
                                @elseif ($requisition->status->value === 'rejected')
                                    <span class="badge text-bg-danger">Rejected</span>
                                @elseif ($requisition->status->value === 'closed')
                                    <span class="badge text-bg-secondary">Closed</span>
                                @else
                                    <span class="badge text-bg-warning">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('recruitment.manage')
                                    @if ($requisition->status->value === 'pending')
                                        <form method="POST" action="{{ route('admin.recruitment.requisitions.approve', $requisition) }}" class="d-inline"
                                              onsubmit="return confirm('Approve this requisition?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $requisition->id }}">Reject</button>

                                        <div class="modal fade" id="rejectModal{{ $requisition->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('admin.recruitment.requisitions.reject', $requisition) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject requisition</h5>
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
                            <td colspan="6" class="text-center text-body-secondary py-3">No job requisitions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $requisitions->links() }}</div>
@endsection
