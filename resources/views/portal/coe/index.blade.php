@extends('layouts.portal')

@section('title', 'Request COE')

@php($breadcrumbs = [['label' => 'Request COE']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Certificate of Employment</h1>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestCoeModal">
                Request COE
            </button>
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
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->coeRequests as $coeRequest)
                            <tr>
                                <td>{{ $coeRequest->created_at->format('M d, Y') }}</td>
                                <td>{{ $coeRequest->type->label() }}</td>
                                <td>{{ $coeRequest->purpose ?? '—' }}</td>
                                <td>
                                    @if ($coeRequest->status->value === 'approved')
                                        <span class="badge text-bg-success">Approved</span>
                                    @elseif ($coeRequest->status->value === 'rejected')
                                        <span class="badge text-bg-danger">Rejected</span>
                                        @if ($coeRequest->rejection_reason)
                                            <div class="text-body-secondary small">{{ $coeRequest->rejection_reason }}</div>
                                        @endif
                                    @else
                                        <span class="badge text-bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($coeRequest->status->value === 'approved')
                                        <a href="{{ route('portal.coe.download', $coeRequest) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-body-secondary py-3">No COE requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="requestCoeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('portal.coe.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Request Certificate of Employment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" required>
                                    @foreach (\App\Enums\CoeRequestType::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purpose (optional)</label>
                                <input type="text" name="purpose" class="form-control" maxlength="255" placeholder="e.g. Visa application">
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
