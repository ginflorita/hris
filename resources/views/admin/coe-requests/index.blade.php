@extends('layouts.admin')

@section('title', 'COE Requests')

@php($breadcrumbs = [['label' => 'COE Requests']])

@section('content')
    <h1 class="h4 mb-3">Certificate of Employment Requests</h1>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Enums\CoeRequestStatus::cases() as $case)
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
                        <th>Type</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coeRequests as $coeRequest)
                        @php($needsSalaryAccess = $coeRequest->type->value === 'with_compensation' && ! auth()->user()->can('employees.salary.view'))
                        <tr>
                            <td>{{ $coeRequest->created_at->format('M d, Y') }}</td>
                            <td>{{ $coeRequest->employee->full_name }}</td>
                            <td>{{ $coeRequest->type->label() }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($coeRequest->purpose ?? '—', 40) }}</td>
                            <td>
                                @if ($coeRequest->status->value === 'approved')
                                    <span class="badge text-bg-success">Approved</span>
                                @elseif ($coeRequest->status->value === 'rejected')
                                    <span class="badge text-bg-danger">Rejected</span>
                                @else
                                    <span class="badge text-bg-warning">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($coeRequest->status->value === 'pending')
                                    @if ($needsSalaryAccess)
                                        <span class="text-body-secondary small">Requires salary access</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.coe-requests.approve', $coeRequest) }}" class="d-inline"
                                              onsubmit="return confirm('Approve and generate this certificate?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $coeRequest->id }}">Reject</button>
                                    @endif

                                    <div class="modal fade" id="rejectModal{{ $coeRequest->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('admin.coe-requests.reject', $coeRequest) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject COE request</h5>
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
                                @elseif ($coeRequest->status->value === 'approved' && ! $needsSalaryAccess)
                                    <a href="{{ route('admin.coe-requests.download', $coeRequest) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-3">No COE requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $coeRequests->links() }}</div>
@endsection
