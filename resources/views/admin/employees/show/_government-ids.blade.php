@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGovernmentIdModal">Add government ID</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Number</th>
                    <th>Issued</th>
                    <th>Expires</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->governmentIds as $governmentId)
                    <tr>
                        <td>{{ strtoupper($governmentId->id_type->value) }}</td>
                        <td>{{ $governmentId->id_number }}</td>
                        <td>{{ $governmentId->issued_at?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $governmentId->expires_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editGovernmentIdModal{{ $governmentId->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.government-ids.destroy', [$employee, $governmentId]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this government ID?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No government IDs on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._government-id-modal', ['governmentId' => null, 'modalId' => 'addGovernmentIdModal'])
    @foreach ($employee->governmentIds as $governmentId)
        @include('admin.employees.show._government-id-modal', ['governmentId' => $governmentId, 'modalId' => 'editGovernmentIdModal'.$governmentId->id])
    @endforeach
@endcan
