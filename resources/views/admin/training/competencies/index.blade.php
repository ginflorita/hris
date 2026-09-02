@extends('layouts.admin')

@section('title', 'Competencies')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Competencies']])

@section('content')
    <x-admin.training-subnav active="competencies" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('training.manage') ? route('admin.training.competencies.create') : null"
        create-label="Add competency"
        error-key="competency"
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
            @forelse ($competencies as $competency)
                <tr>
                    <td>{{ $competency->name }}</td>
                    <td>{{ $competency->company->name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($competency->description, 60) ?: '—' }}</td>
                    <td>
                        @if ($competency->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('training.manage')
                            <a href="{{ route('admin.training.competencies.edit', $competency) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.training.competencies.destroy', $competency) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $competency->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No competencies yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $competencies->links() }}</div>
@endsection
