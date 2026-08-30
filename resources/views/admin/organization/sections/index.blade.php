@extends('layouts.admin')

@section('title', 'Sections')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Sections']])

@section('content')
    <x-admin.org-subnav active="sections" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.sections.create') : null"
        create-label="Add section"
        error-key="section"
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
            @forelse ($sections as $section)
                <tr>
                    <td>{{ $section->name }}</td>
                    <td>{{ $section->company->name }}</td>
                    <td>{{ $section->department?->name ?? '—' }}</td>
                    <td><code>{{ $section->code }}</code></td>
                    <td>
                        @if ($section->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.sections.edit', $section) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.sections.destroy', $section) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $section->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No sections yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $sections->links() }}</div>
@endsection
