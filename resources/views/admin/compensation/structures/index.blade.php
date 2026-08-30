@extends('layouts.admin')

@section('title', 'Salary Structures')

@php($breadcrumbs = [['label' => 'Compensation'], ['label' => 'Salary Structures']])

@section('content')
    <x-admin.compensation-subnav active="structures" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.compensation.structures.create') : null"
        create-label="Add structure"
        error-key="salaryStructure"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Effective date</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($salaryStructures as $salaryStructure)
                <tr>
                    <td>{{ $salaryStructure->name }}</td>
                    <td>{{ $salaryStructure->company->name }}</td>
                    <td><code>{{ $salaryStructure->code }}</code></td>
                    <td>{{ $salaryStructure->effective_date->format('M d, Y') }}</td>
                    <td>
                        @if ($salaryStructure->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.compensation.structures.edit', $salaryStructure) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.compensation.structures.destroy', $salaryStructure) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $salaryStructure->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No salary structures yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $salaryStructures->links() }}</div>
@endsection
