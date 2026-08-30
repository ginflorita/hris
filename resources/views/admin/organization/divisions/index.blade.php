@extends('layouts.admin')

@section('title', 'Divisions')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Divisions']])

@section('content')
    <x-admin.org-subnav active="divisions" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.divisions.create') : null"
        create-label="Add division"
        error-key="division"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($divisions as $division)
                <tr>
                    <td>{{ $division->name }}</td>
                    <td>{{ $division->company->name }}</td>
                    <td><code>{{ $division->code }}</code></td>
                    <td>
                        @if ($division->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.divisions.edit', $division) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.divisions.destroy', $division) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $division->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No divisions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $divisions->links() }}</div>
@endsection
