@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDependentModal">Add dependent</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Relationship</th>
                    <th>Birth date</th>
                    <th>Beneficiary</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->dependents as $dependent)
                    <tr>
                        <td>{{ $dependent->name }}</td>
                        <td>{{ $dependent->relationship }}</td>
                        <td>{{ $dependent->birth_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $dependent->is_beneficiary ? 'Yes' : '' }}</td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editDependentModal{{ $dependent->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.dependents.destroy', [$employee, $dependent]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this dependent?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No dependents on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._dependent-modal', ['dependent' => null, 'modalId' => 'addDependentModal'])
    @foreach ($employee->dependents as $dependent)
        @include('admin.employees.show._dependent-modal', ['dependent' => $dependent, 'modalId' => 'editDependentModal'.$dependent->id])
    @endforeach
@endcan
