<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $bracket ? route('admin.payroll.contribution-rate-tables.brackets.update', [$contributionRateTable, $bracket]) : route('admin.payroll.contribution-rate-tables.brackets.store', $contributionRateTable) }}">
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
                            <label class="form-label">Min salary</label>
                            <input type="number" step="0.01" min="0" name="min_salary" value="{{ $bracket?->min_salary }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Max salary</label>
                            <input type="number" step="0.01" min="0" name="max_salary" value="{{ $bracket?->max_salary }}" class="form-control">
                            <div class="form-text">Leave blank for the top, open-ended bracket.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Employee share</label>
                            <input type="number" step="0.01" min="0" name="employee_amount" value="{{ $bracket?->employee_amount }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Employer share</label>
                            <input type="number" step="0.01" min="0" name="employer_amount" value="{{ $bracket?->employer_amount }}" class="form-control" required>
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
