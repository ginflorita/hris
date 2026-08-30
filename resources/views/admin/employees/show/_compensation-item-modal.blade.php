<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $item ? route('admin.employees.compensation-items.update', [$employee, $item]) : route('admin.employees.compensation-items.store', $employee) }}">
                @csrf
                @if ($item)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $item ? 'Edit compensation item' : 'Add compensation item' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Enums\CompensationItemType::cases() as $case)
                                <option value="{{ $case->value }}" {{ $item?->type?->value === $case->value ? 'selected' : '' }}>
                                    {{ ucfirst($case->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ $item?->name }}" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0" name="amount" value="{{ $item?->amount }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Frequency</label>
                            <select name="frequency" class="form-select" required>
                                @foreach (\App\Enums\CompensationFrequency::cases() as $case)
                                    <option value="{{ $case->value }}" {{ $item?->frequency?->value === $case->value ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $case->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Effective date</label>
                            <input type="date" name="effective_date" value="{{ $item?->effective_date?->format('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End date</label>
                            <input type="date" name="end_date" value="{{ $item?->end_date?->format('Y-m-d') }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control">{{ $item?->remarks }}</textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $modalId }}_active" {{ $item?->is_active ?? true ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_active">Active</label>
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
