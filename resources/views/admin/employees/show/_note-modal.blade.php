<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $note ? route('admin.employees.notes.update', [$employee, $note]) : route('admin.employees.notes.store', $employee) }}">
                @csrf
                @if ($note)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $note ? 'Edit note' : 'Add note' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" rows="4" class="form-control" required>{{ $note?->note }}</textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_confidential" value="1" id="{{ $modalId }}_confidential" {{ $note?->is_confidential ?? true ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_confidential">Confidential</label>
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
