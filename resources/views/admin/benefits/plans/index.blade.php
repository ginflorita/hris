@extends('layouts.admin')

@section('title', 'Benefit Plans')

@php($breadcrumbs = [['label' => 'Benefits'], ['label' => 'Plans']])

@section('content')
    <x-admin.resource-index
        :create-url="auth()->user()->can('benefits.manage') ? route('admin.benefits.plans.create') : null"
        create-label="Add plan"
        error-key="plan"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($plans as $plan)
                <tr>
                    <td>{{ $plan->name }}</td>
                    <td>{{ $plan->company->name }}</td>
                    <td>{{ $plan->type->label() }}</td>
                    <td>
                        @if ($plan->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('benefits.manage')
                            <a href="{{ route('admin.benefits.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.benefits.plans.destroy', $plan) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $plan->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No benefit plans yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $plans->links() }}</div>
@endsection
