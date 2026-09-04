@extends('layouts.portal')

@section('title', 'Update My Information')

@php($breadcrumbs = [['label' => 'Update My Information']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Update My Information</h1>
            @if ($definitionAvailable)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestChangeModal">
                    Request a Change
                </button>
            @endif
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @unless ($definitionAvailable)
            <div class="alert alert-warning">
                No approval workflow is configured for information changes yet. Contact HR.
            </div>
        @endunless

        <div class="card">
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Fields</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->informationChangeRequests as $changeRequest)
                            @php($fields = collect([
                                'requested_mobile' => 'Mobile',
                                'requested_email' => 'Email',
                                'requested_civil_status' => 'Civil status',
                                'requested_nationality' => 'Nationality',
                            ])->filter(fn ($label, $key) => $changeRequest->{$key} !== null)->values())
                            @php($status = $changeRequest->workflowInstance?->status)
                            @php($statusBadge = match ($status) {
                                \App\Enums\WorkflowInstanceStatus::Approved => 'text-bg-success',
                                \App\Enums\WorkflowInstanceStatus::Rejected => 'text-bg-danger',
                                \App\Enums\WorkflowInstanceStatus::Cancelled => 'text-bg-secondary',
                                default => 'text-bg-warning',
                            })
                            <tr>
                                <td>{{ $changeRequest->created_at->format('M d, Y') }}</td>
                                <td>{{ $fields->implode(', ') }}</td>
                                <td>{{ $changeRequest->reason }}</td>
                                <td><span class="badge {{ $statusBadge }}">{{ ucwords(str_replace('_', ' ', $status?->value ?? 'unknown')) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-secondary py-3">No information change requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($definitionAvailable)
            <div class="modal fade" id="requestChangeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('portal.information-change.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Request an Information Change</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-body-secondary small">Leave a field blank to leave it unchanged. Your current information isn't updated until HR approves this request.</p>
                                <div class="mb-3">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="requested_mobile" class="form-control" value="{{ old('requested_mobile') }}" placeholder="{{ $employee->mobile }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="requested_email" class="form-control" value="{{ old('requested_email') }}" placeholder="{{ $employee->email }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Civil status</label>
                                    <select name="requested_civil_status" class="form-select">
                                        <option value="">No change</option>
                                        @foreach (\App\Enums\CivilStatus::cases() as $case)
                                            <option value="{{ $case->value }}" {{ old('requested_civil_status') === $case->value ? 'selected' : '' }}>
                                                {{ ucfirst($case->value) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="requested_nationality" class="form-control" value="{{ old('requested_nationality') }}" placeholder="{{ $employee->nationality }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea name="reason" rows="3" class="form-control" required>{{ old('reason') }}</textarea>
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
        @endif
    @endunless
@endsection
