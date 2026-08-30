@extends('layouts.admin')

@section('title', 'Job Levels')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Job Levels']])

@section('content')
    <x-admin.org-subnav active="job-levels" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.job-levels.create') : null"
        create-label="Add job level"
        error-key="jobLevel"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Rank</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jobLevels as $jobLevel)
                <tr>
                    <td>{{ $jobLevel->name }}</td>
                    <td>{{ $jobLevel->company->name }}</td>
                    <td><code>{{ $jobLevel->code }}</code></td>
                    <td>{{ $jobLevel->rank }}</td>
                    <td>
                        @if ($jobLevel->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.job-levels.edit', $jobLevel) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.job-levels.destroy', $jobLevel) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $jobLevel->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No job levels yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $jobLevels->links() }}</div>
@endsection
