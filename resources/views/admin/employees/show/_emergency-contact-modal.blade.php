<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $emergencyContact ? route('admin.employees.emergency-contacts.update', [$employee, $emergencyContact]) : route('admin.employees.emergency-contacts.store', $employee) }}">
                @csrf
                @if ($emergencyContact)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $emergencyContact ? 'Edit emergency contact' : 'Add emergency contact' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ $emergencyContact?->name }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="relationship" value="{{ $emergencyContact?->relationship }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ $emergencyContact?->phone }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="2" class="form-control">{{ $emergencyContact?->address }}</textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="{{ $modalId }}_primary" {{ $emergencyContact?->is_primary ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_primary">Primary emergency contact</label>
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
