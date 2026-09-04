@extends('layouts.admin')

@section('title', 'Workflows')

@php($breadcrumbs = [['label' => 'Workflows']])

@section('content')
    <x-admin.resource-index
        :create-url="auth()->user()->can('workflow.manage') ? route('admin.workflow.definitions.create') : null"
        create-label="Add workflow"
        error-key="workflowDefinition"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Process</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($workflowDefinitions as $definition)
                <tr>
                    <td><a href="{{ route('admin.workflow.definitions.show', $definition) }}">{{ $definition->name }}</a></td>
                    <td>{{ $definition->company->name }}</td>
                    <td>{{ $definition->process_type->label() }}</td>
                    <td>
                        @if ($definition->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.workflow.definitions.show', $definition) }}" class="btn btn-sm btn-outline-secondary">Steps</a>
                        @can('workflow.manage')
                            <a href="{{ route('admin.workflow.definitions.edit', $definition) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.workflow.definitions.destroy', $definition) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $definition->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No workflows yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $workflowDefinitions->links() }}</div>
@endsection
