<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $bracket ? route('admin.payroll.tax-tables.brackets.update', [$taxTable, $bracket]) : route('admin.payroll.tax-tables.brackets.store', $taxTable) }}">
                @csrf
                @if ($bracket)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $bracket ? 'Edit bracket' : 'Add bracket' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Order</label>
                        <input type="number" min="0" name="order" value="{{ $bracket?->order ?? 0 }}" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Min income</label>
                            <input type="number" step="0.01" min="0" name="min_income" value="{{ $bracket?->min_income }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Max income</label>
                            <input type="number" step="0.01" min="0" name="max_income" value="{{ $bracket?->max_income }}" class="form-control">
                            <div class="form-text">Leave blank for the top, open-ended bracket.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Base tax</label>
                            <input type="number" step="0.01" min="0" name="base_tax" value="{{ $bracket?->base_tax }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Excess rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="excess_rate_percent" value="{{ $bracket?->excess_rate_percent }}" class="form-control" required>
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
