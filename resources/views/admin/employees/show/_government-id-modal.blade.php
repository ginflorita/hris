<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $governmentId ? route('admin.employees.government-ids.update', [$employee, $governmentId]) : route('admin.employees.government-ids.store', $employee) }}">
                @csrf
                @if ($governmentId)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $governmentId ? 'Edit government ID' : 'Add government ID' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="id_type" class="form-select" required>
                            @foreach (\App\Enums\GovernmentIdType::cases() as $case)
                                <option value="{{ $case->value }}" {{ $governmentId?->id_type?->value === $case->value ? 'selected' : '' }}>
                                    {{ strtoupper($case->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID number</label>
                        <input type="text" name="id_number" value="{{ $governmentId?->id_number }}" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Issued</label>
                            <input type="date" name="issued_at" value="{{ $governmentId?->issued_at?->format('Y-m-d') }}" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Expires</label>
                            <input type="date" name="expires_at" value="{{ $governmentId?->expires_at?->format('Y-m-d') }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
