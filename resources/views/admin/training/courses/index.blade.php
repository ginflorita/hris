@extends('layouts.admin')

@section('title', 'Training Courses')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Courses']])

@section('content')
    <x-admin.training-subnav active="courses" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('training.manage') ? route('admin.training.courses.create') : null"
        create-label="Add course"
        error-key="course"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Provider</th>
                <th>Duration</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td><a href="{{ route('admin.training.courses.show', $course) }}">{{ $course->name }}</a></td>
                    <td>{{ $course->company->name }}</td>
                    <td>{{ $course->provider?->name ?? '—' }}</td>
                    <td>{{ $course->duration_hours ? "{$course->duration_hours}h" : '—' }}</td>
                    <td>
                        @if ($course->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.training.courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @can('training.manage')
                            <a href="{{ route('admin.training.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No training courses yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $courses->links() }}</div>
@endsection
