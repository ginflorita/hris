@extends('layouts.admin')

@section('title', 'Skills')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Skills']])

@section('content')
    <x-admin.training-subnav active="skills" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('training.manage') ? route('admin.training.skills.create') : null"
        create-label="Add skill"
        error-key="skill"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Description</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($skills as $skill)
                <tr>
                    <td>{{ $skill->name }}</td>
                    <td>{{ $skill->company->name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($skill->description, 60) ?: '—' }}</td>
                    <td>
                        @if ($skill->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('training.manage')
                            <a href="{{ route('admin.training.skills.edit', $skill) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.training.skills.destroy', $skill) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $skill->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No skills yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $skills->links() }}</div>
@endsection
