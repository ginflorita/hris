@extends('layouts.admin')

@section('title', 'Cost Centers')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Cost Centers']])

@section('content')
    <x-admin.org-subnav active="cost-centers" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.cost-centers.create') : null"
        create-label="Add cost center"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Department</th>
                <th>Code</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($costCenters as $costCenter)
                <tr>
                    <td>{{ $costCenter->name }}</td>
                    <td>{{ $costCenter->company->name }}</td>
                    <td>{{ $costCenter->department?->name ?? '—' }}</td>
                    <td><code>{{ $costCenter->code }}</code></td>
                    <td>
                        @if ($costCenter->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.cost-centers.edit', $costCenter) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.cost-centers.destroy', $costCenter) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $costCenter->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No cost centers yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $costCenters->links() }}</div>
@endsection
