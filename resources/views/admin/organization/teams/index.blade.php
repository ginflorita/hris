@extends('layouts.admin')

@section('title', 'Teams')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Teams']])

@section('content')
    <x-admin.org-subnav active="teams" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.teams.create') : null"
        create-label="Add team"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Department</th>
                <th>Section</th>
                <th>Code</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($teams as $team)
                <tr>
                    <td>{{ $team->name }}</td>
                    <td>{{ $team->company->name }}</td>
                    <td>{{ $team->department?->name ?? '—' }}</td>
                    <td>{{ $team->section?->name ?? '—' }}</td>
                    <td><code>{{ $team->code }}</code></td>
                    <td>
                        @if ($team->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.teams.edit', $team) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.teams.destroy', $team) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $team->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-body-secondary py-3">No teams yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $teams->links() }}</div>
@endsection
