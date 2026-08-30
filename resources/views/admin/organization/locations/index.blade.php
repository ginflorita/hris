@extends('layouts.admin')

@section('title', 'Locations')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Locations']])

@section('content')
    <x-admin.org-subnav active="locations" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.locations.create') : null"
        create-label="Add location"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Branch</th>
                <th>Code</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($locations as $location)
                <tr>
                    <td>{{ $location->name }}</td>
                    <td>{{ $location->company->name }}</td>
                    <td>{{ $location->branch?->name ?? '—' }}</td>
                    <td><code>{{ $location->code }}</code></td>
                    <td>
                        @if ($location->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.locations.edit', $location) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.locations.destroy', $location) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $location->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No locations yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $locations->links() }}</div>
@endsection
