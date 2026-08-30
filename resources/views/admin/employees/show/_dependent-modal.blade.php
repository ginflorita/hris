<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $dependent ? route('admin.employees.dependents.update', [$employee, $dependent]) : route('admin.employees.dependents.store', $employee) }}">
                @csrf
                @if ($dependent)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $dependent ? 'Edit dependent' : 'Add dependent' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ $dependent?->name }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="relationship" value="{{ $dependent?->relationship }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Birth date</label>
                        <input type="date" name="birth_date" value="{{ $dependent?->birth_date?->format('Y-m-d') }}" class="form-control">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_beneficiary" value="1" id="{{ $modalId }}_beneficiary" {{ $dependent?->is_beneficiary ?? true ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_beneficiary">Beneficiary</label>
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
