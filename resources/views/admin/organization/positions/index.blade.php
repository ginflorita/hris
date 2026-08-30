@extends('layouts.admin')

@section('title', 'Positions')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Positions']])

@section('content')
    <x-admin.org-subnav active="positions" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.positions.create') : null"
        create-label="Add position"
    >
        <thead>
            <tr>
                <th>Title</th>
                <th>Company</th>
                <th>Department</th>
                <th>Job Level</th>
                <th>Job Grade</th>
                <th>Code</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($positions as $position)
                <tr>
                    <td>{{ $position->title }}</td>
                    <td>{{ $position->company->name }}</td>
                    <td>{{ $position->department?->name ?? '—' }}</td>
                    <td>{{ $position->jobLevel?->name ?? '—' }}</td>
                    <td>{{ $position->jobGrade?->name ?? '—' }}</td>
                    <td><code>{{ $position->code }}</code></td>
                    <td>
                        @if ($position->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.positions.edit', $position) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.positions.destroy', $position) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $position->title }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-body-secondary py-3">No positions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $positions->links() }}</div>
@endsection
