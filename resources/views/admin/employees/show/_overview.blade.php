@php($rows = [
    'Full name' => $employee->full_name,
    'Preferred name' => $employee->preferred_name,
    'Birth date' => $employee->birth_date?->format('M d, Y'),
    'Gender' => $employee->gender ? ucwords(str_replace('_', ' ', $employee->gender->value)) : null,
    'Civil status' => $employee->civil_status ? ucfirst($employee->civil_status->value) : null,
    'Nationality' => $employee->nationality,
    'Email' => $employee->email,
    'Mobile' => $employee->mobile,
    'Company' => $employee->company->name,
])

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="row mb-0">
            @foreach ($rows as $label => $value)
                <dt class="col-sm-4 text-body-secondary fw-normal">{{ $label }}</dt>
                <dd class="col-sm-8">{{ $value ?? '—' }}</dd>
            @endforeach
        </dl>
    </div>
</div>
