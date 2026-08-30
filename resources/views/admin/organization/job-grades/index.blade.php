@extends('layouts.admin')

@section('title', 'Job Grades')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Job Grades']])

@section('content')
    <x-admin.org-subnav active="job-grades" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.job-grades.create') : null"
        create-label="Add job grade"
        error-key="jobGrade"
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
            @forelse ($jobGrades as $jobGrade)
                <tr>
                    <td>{{ $jobGrade->name }}</td>
                    <td>{{ $jobGrade->company->name }}</td>
                    <td><code>{{ $jobGrade->code }}</code></td>
                    <td>{{ $jobGrade->rank }}</td>
                    <td>
                        @if ($jobGrade->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.job-grades.edit', $jobGrade) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.job-grades.destroy', $jobGrade) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $jobGrade->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No job grades yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $jobGrades->links() }}</div>
@endsection
