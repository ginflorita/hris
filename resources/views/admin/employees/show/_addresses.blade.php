@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">Add address</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Primary</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->addresses as $address)
                    <tr>
                        <td>{{ ucfirst($address->type->value) }}</td>
                        <td>{{ $address->line1 }}{{ $address->line2 ? ', '.$address->line2 : '' }}</td>
                        <td>{{ $address->city }}, {{ $address->province_state }} {{ $address->postal_code }}</td>
                        <td>{{ $address->is_primary ? 'Yes' : '' }}</td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAddressModal{{ $address->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.addresses.destroy', [$employee, $address]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this address?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No addresses on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._address-modal', ['address' => null, 'modalId' => 'addAddressModal'])
    @foreach ($employee->addresses as $address)
        @include('admin.employees.show._address-modal', ['address' => $address, 'modalId' => 'editAddressModal'.$address->id])
    @endforeach
@endcan
