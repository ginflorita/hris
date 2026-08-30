<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $address ? route('admin.employees.addresses.update', [$employee, $address]) : route('admin.employees.addresses.store', $employee) }}">
                @csrf
                @if ($address)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $address ? 'Edit address' : 'Add address' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Enums\AddressType::cases() as $case)
                                <option value="{{ $case->value }}" {{ $address?->type?->value === $case->value ? 'selected' : '' }}>
                                    {{ ucfirst($case->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address line 1</label>
                        <input type="text" name="line1" value="{{ $address?->line1 }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address line 2</label>
                        <input type="text" name="line2" value="{{ $address?->line2 }}" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" value="{{ $address?->city }}" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Province/State</label>
                            <input type="text" name="province_state" value="{{ $address?->province_state }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Postal code</label>
                            <input type="text" name="postal_code" value="{{ $address?->postal_code }}" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" value="{{ $address?->country ?? 'Philippines' }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="{{ $modalId }}_primary" {{ $address?->is_primary ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_primary">Primary address</label>
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
