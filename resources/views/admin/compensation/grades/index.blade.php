@extends('layouts.admin')

@section('title', 'Salary Grades')

@php($breadcrumbs = [['label' => 'Compensation'], ['label' => 'Salary Grades']])

@section('content')
    <x-admin.compensation-subnav active="grades" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.compensation.grades.create') : null"
        create-label="Add grade"
        error-key="salaryGrade"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Structure</th>
                <th>Code</th>
                <th>Range</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($salaryGrades as $salaryGrade)
                <tr>
                    <td>{{ $salaryGrade->name }}</td>
                    <td>{{ $salaryGrade->company->name }}</td>
                    <td>{{ $salaryGrade->salaryStructure->name }}</td>
                    <td><code>{{ $salaryGrade->code }}</code></td>
                    <td>{{ number_format($salaryGrade->min_salary, 2) }} – {{ number_format($salaryGrade->max_salary, 2) }}</td>
                    <td>
                        @if ($salaryGrade->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.compensation.grades.edit', $salaryGrade) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.compensation.grades.destroy', $salaryGrade) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $salaryGrade->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-body-secondary py-3">No salary grades yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $salaryGrades->links() }}</div>
@endsection
