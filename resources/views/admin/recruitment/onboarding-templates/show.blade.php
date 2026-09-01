@extends('layouts.admin')

@section('title', $onboardingTemplate->name)

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Onboarding Templates', 'url' => route('admin.recruitment.onboarding-templates.index')], ['label' => $onboardingTemplate->name]])

@section('content')
    <x-admin.recruitment-subnav active="onboarding-templates" />

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">
                    {{ $onboardingTemplate->name }}
                    @if ($onboardingTemplate->is_active)
                        <span class="badge text-bg-success align-middle">Active</span>
                    @else
                        <span class="badge text-bg-secondary align-middle">Inactive</span>
                    @endif
                </h1>
                <div class="text-body-secondary small">{{ $onboardingTemplate->company->name }}</div>
                @if ($onboardingTemplate->description)
                    <p class="mb-0 mt-2">{{ $onboardingTemplate->description }}</p>
                @endif
            </div>
            @can('recruitment.manage')
                <a href="{{ route('admin.recruitment.onboarding-templates.edit', $onboardingTemplate) }}" class="btn btn-sm btn-outline-secondary">Edit template</a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @can('recruitment.manage')
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">Add task</button>
        </div>
    @endcan

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($onboardingTemplate->tasks as $task)
                        <tr>
                            <td>{{ $task->sequence }}</td>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->description ?? '—' }}</td>
                            <td class="text-end">
                                @can('recruitment.manage')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTaskModal{{ $task->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.recruitment.onboarding-templates.tasks.destroy', [$onboardingTemplate, $task]) }}" class="d-inline"
                                          onsubmit="return confirm('Remove this task?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-3">No tasks yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('recruitment.manage')
        @include('admin.recruitment.onboarding-templates._task-modal', ['task' => null, 'modalId' => 'addTaskModal'])
        @foreach ($onboardingTemplate->tasks as $task)
            @include('admin.recruitment.onboarding-templates._task-modal', ['task' => $task, 'modalId' => 'editTaskModal'.$task->id])
        @endforeach
    @endcan
@endsection
