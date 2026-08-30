<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $contact ? route('admin.employees.contacts.update', [$employee, $contact]) : route('admin.employees.contacts.store', $employee) }}">
                @csrf
                @if ($contact)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $contact ? 'Edit contact' : 'Add contact' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Enums\ContactType::cases() as $case)
                                <option value="{{ $case->value }}" {{ $contact?->type?->value === $case->value ? 'selected' : '' }}>
                                    {{ ucfirst($case->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="text" name="value" value="{{ $contact?->value }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" value="{{ $contact?->label }}" class="form-control" placeholder="e.g. Work, Personal">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="{{ $modalId }}_primary" {{ $contact?->is_primary ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_primary">Primary contact</label>
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
