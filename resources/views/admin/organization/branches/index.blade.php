@extends('layouts.admin')

@section('title', 'Branches')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Branches']])

@section('content')
    <x-admin.org-subnav active="branches" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.branches.create') : null"
        create-label="Add branch"
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
            @forelse ($branches as $branch)
                <tr>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->company->name }}</td>
                    <td><code>{{ $branch->code }}</code></td>
                    <td>
                        @if ($branch->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.branches.destroy', $branch) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $branch->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No branches yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $branches->links() }}</div>
@endsection
