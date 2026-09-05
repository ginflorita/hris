@extends('layouts.admin')

@section('title', $workflowDefinition->name)

@php($breadcrumbs = [['label' => 'Workflows', 'url' => route('admin.workflow.definitions.index')], ['label' => $workflowDefinition->name]])

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">{{ $workflowDefinition->name }}</h1>
                <div class="text-body-secondary small">
                    {{ $workflowDefinition->company->name }} &middot; {{ $workflowDefinition->process_type->label() }}
                    &middot;
                    @if ($workflowDefinition->is_active)
                        <span class="badge text-bg-success">Active</span>
                    @else
                        <span class="badge text-bg-secondary">Inactive</span>
                    @endif
                </div>
                @if ($workflowDefinition->description)
                    <p class="mt-2 mb-0">{{ $workflowDefinition->description }}</p>
                @endif
            </div>
            @can('workflow.manage')
                <a href="{{ route('admin.workflow.definitions.edit', $workflowDefinition) }}" class="btn btn-sm btn-outline-secondary">Edit workflow</a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @can('workflow.manage')
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStepModal">Add step</button>
        </div>
    @endcan

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Step</th>
                        <th>Approver</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workflowDefinition->steps as $step)
                        <tr>
                            <td>{{ $step->step_order }}</td>
                            <td>{{ $step->name }}</td>
                            <td>
                                @if ($step->approver_type === \App\Enums\WorkflowApproverType::Manager)
                                    The requester's manager
                                @else
                                    Anyone with <code>{{ $step->required_permission }}</code>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('workflow.manage')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editStepModal{{ $step->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.workflow.definitions.steps.destroy', [$workflowDefinition, $step]) }}" class="d-inline"
                                          onsubmit="return confirm('Remove this step?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-3">No steps yet — this workflow can't be used until it has at least one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('workflow.manage')
        @include('admin.workflow.definitions._step-modal', ['step' => null, 'modalId' => 'addStepModal'])
        @foreach ($workflowDefinition->steps as $step)
            @include('admin.workflow.definitions._step-modal', ['step' => $step, 'modalId' => 'editStepModal'.$step->id])
        @endforeach
    @endcan
@endsection
