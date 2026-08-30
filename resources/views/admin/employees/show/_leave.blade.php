@can('leave.create')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#adjustBalanceModal">Adjust balance</button>
    </div>
@endcan

<div class="card mb-3">
    <div class="card-header">Balances</div>
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Leave type</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->leaveBalances as $balance)
                    <tr>
                        <td>{{ $balance->leaveType->name }}</td>
                        <td>{{ $balance->balance }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-body-secondary py-3">No leave balances yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Transaction history</div>
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Leave type</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance after</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->leaveTransactions as $transaction)
                    <tr>
                        <td>{{ $transaction->date->format('M d, Y') }}</td>
                        <td>{{ $transaction->leaveType->name }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $transaction->type->value)) }}</td>
                        <td class="{{ $transaction->amount < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }}
                        </td>
                        <td>{{ $transaction->balance_after }}</td>
                        <td>{{ $transaction->reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-body-secondary py-3">No leave transactions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('leave.create')
    <div class="modal fade" id="adjustBalanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.leave-balance.adjust', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Adjust leave balance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Leave type</label>
                            <select name="leave_type_id" class="form-select" required>
                                @foreach ($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (days)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                            <div class="form-text">Positive to credit, negative to debit.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" class="form-control" required>
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
@endcan
