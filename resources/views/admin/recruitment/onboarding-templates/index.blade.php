@extends('layouts.admin')

@section('title', 'Onboarding Templates')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Onboarding Templates']])

@section('content')
    <x-admin.recruitment-subnav active="onboarding-templates" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('recruitment.manage') ? route('admin.recruitment.onboarding-templates.create') : null"
        create-label="Add template"
        error-key="onboardingTemplate"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Tasks</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($onboardingTemplates as $template)
                <tr>
                    <td><a href="{{ route('admin.recruitment.onboarding-templates.show', $template) }}">{{ $template->name }}</a></td>
                    <td>{{ $template->company->name }}</td>
                    <td>{{ $template->tasks_count }}</td>
                    <td>
                        @if ($template->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.recruitment.onboarding-templates.show', $template) }}" class="btn btn-sm btn-outline-secondary">Tasks</a>
                        @can('recruitment.manage')
                            <a href="{{ route('admin.recruitment.onboarding-templates.edit', $template) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.recruitment.onboarding-templates.destroy', $template) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $template->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No onboarding templates yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $onboardingTemplates->links() }}</div>
@endsection
