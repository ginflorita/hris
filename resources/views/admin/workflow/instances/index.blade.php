@extends('layouts.admin')

@section('title', 'My Approvals')

@php($breadcrumbs = [['label' => 'My Approvals']])

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">My Approvals</h1>
        <p class="text-body-secondary mb-0">Requests currently awaiting your decision, across every workflow.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Workflow</th>
                        <th>Request</th>
                        <th>Step</th>
                        <th>Requested</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingSteps as $step)
                        @php($instance = $step->workflowInstance)
                        <tr>
                            <td>{{ $instance->workflowDefinition->name }}</td>
                            <td>
                                @if ($instance->subject instanceof \App\Models\EmployeeInformationChangeRequest)
                                    {{ $instance->subject->employee->full_name }} &mdash; Information change
                                @else
                                    Request #{{ $instance->id }}
                                @endif
                            </td>
                            <td>{{ $step->name }}</td>
                            <td>{{ $instance->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.workflow.instances.show', $instance) }}" class="btn btn-sm btn-outline-secondary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-3">Nothing awaiting your action.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
