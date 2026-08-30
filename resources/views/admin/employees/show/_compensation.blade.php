@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCompensationItemModal">Add item</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Amount</th>
                    <th>Frequency</th>
                    <th>Effective</th>
                    <th>Ends</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->compensationItems as $item)
                    <tr>
                        <td>{{ ucfirst($item->type->value) }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ number_format($item->amount, 2) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $item->frequency->value)) }}</td>
                        <td>{{ $item->effective_date->format('M d, Y') }}</td>
                        <td>{{ $item->end_date?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @if ($item->is_active)
                                <span class="badge text-bg-success">Active</span>
                            @else
                                <span class="badge text-bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCompensationItemModal{{ $item->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.compensation-items.destroy', [$employee, $item]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this compensation item?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-body-secondary py-3">No allowances, bonuses, or incentives on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._compensation-item-modal', ['item' => null, 'modalId' => 'addCompensationItemModal'])
    @foreach ($employee->compensationItems as $item)
        @include('admin.employees.show._compensation-item-modal', ['item' => $item, 'modalId' => 'editCompensationItemModal'.$item->id])
    @endforeach
@endcan
