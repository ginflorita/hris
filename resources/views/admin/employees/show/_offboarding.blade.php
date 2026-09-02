@php($activeRequest = $employee->offboardingRequests->first(fn ($r) => ! $r->status->isTerminal()))

@can('employees.update')
    @if (! $activeRequest)
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOffboardingModal">Start Offboarding</button>
        </div>
    @endif
@endcan

@if ($activeRequest)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">Current Status: {{ $activeRequest->status->label() }}</h6>
                    <div class="text-body-secondary small">Resignation date {{ $activeRequest->resignation_date->format('M d, Y') }}</div>
                    @if ($activeRequest->reason)
                        <p class="mt-2 mb-0">{{ $activeRequest->reason }}</p>
                    @endif
                </div>
                @can('employees.update')
                    <div class="text-end">
                        @if ($activeRequest->status->next())
                            <form method="POST" action="{{ route('admin.employees.offboarding-requests.advance', [$employee, $activeRequest]) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-outline-success">Advance to {{ $activeRequest->status->next()->label() }}</button>
                            </form>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOffboardingModal">Cancel</button>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    @can('employees.update')
        <div class="modal fade" id="cancelOffboardingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.employees.offboarding-requests.cancel', [$employee, $activeRequest]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Cancel Offboarding</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="cancellation_reason">Reason</label>
                                <textarea id="cancellation_reason" name="cancellation_reason" rows="2" class="form-control" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Cancel Offboarding</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif

<h6 class="mt-4">History</h6>

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Resignation Date</th>
                    <th>Status</th>
                    <th>Approved</th>
                    <th>Cancellation Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->offboardingRequests as $offboardingRequest)
                    <tr>
                        <td>{{ $offboardingRequest->resignation_date->format('M d, Y') }}</td>
                        <td>{{ $offboardingRequest->status->label() }}</td>
                        <td>{{ $offboardingRequest->approvedBy?->name ?? '—' }}</td>
                        <td>{{ $offboardingRequest->cancellation_reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-body-secondary py-3">No offboarding history.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    <div class="modal fade" id="addOffboardingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.offboarding-requests.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Start Offboarding</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="resignation_date">Resignation Date</label>
                            <input type="date" id="resignation_date" name="resignation_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reason">Reason</label>
                            <textarea id="reason" name="reason" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Start Offboarding</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
