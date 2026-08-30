@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContactModal">Add contact</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Label</th>
                    <th>Primary</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->contacts as $contact)
                    <tr>
                        <td>{{ ucfirst($contact->type->value) }}</td>
                        <td>{{ $contact->value }}</td>
                        <td>{{ $contact->label ?? '—' }}</td>
                        <td>{{ $contact->is_primary ? 'Yes' : '' }}</td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editContactModal{{ $contact->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.contacts.destroy', [$employee, $contact]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this contact?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No contacts on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._contact-modal', ['contact' => null, 'modalId' => 'addContactModal'])
    @foreach ($employee->contacts as $contact)
        @include('admin.employees.show._contact-modal', ['contact' => $contact, 'modalId' => 'editContactModal'.$contact->id])
    @endforeach
@endcan
