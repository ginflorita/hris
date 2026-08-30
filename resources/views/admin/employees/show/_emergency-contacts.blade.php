@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmergencyContactModal">Add emergency contact</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Relationship</th>
                    <th>Phone</th>
                    <th>Primary</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->emergencyContacts as $emergencyContact)
                    <tr>
                        <td>{{ $emergencyContact->name }}</td>
                        <td>{{ $emergencyContact->relationship }}</td>
                        <td>{{ $emergencyContact->phone }}</td>
                        <td>{{ $emergencyContact->is_primary ? 'Yes' : '' }}</td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editEmergencyContactModal{{ $emergencyContact->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.emergency-contacts.destroy', [$employee, $emergencyContact]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this emergency contact?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No emergency contacts on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._emergency-contact-modal', ['emergencyContact' => null, 'modalId' => 'addEmergencyContactModal'])
    @foreach ($employee->emergencyContacts as $emergencyContact)
        @include('admin.employees.show._emergency-contact-modal', ['emergencyContact' => $emergencyContact, 'modalId' => 'editEmergencyContactModal'.$emergencyContact->id])
    @endforeach
@endcan
