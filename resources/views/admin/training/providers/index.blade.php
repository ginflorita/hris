@extends('layouts.admin')

@section('title', 'Training Providers')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Providers']])

@section('content')
    <x-admin.training-subnav active="providers" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('training.manage') ? route('admin.training.providers.create') : null"
        create-label="Add provider"
        error-key="provider"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($providers as $provider)
                <tr>
                    <td>{{ $provider->name }}</td>
                    <td>{{ $provider->company->name }}</td>
                    <td>
                        {{ $provider->contact_name ?? '—' }}
                        @if ($provider->contact_email)
                            <div class="text-body-secondary small">{{ $provider->contact_email }}</div>
                        @endif
                    </td>
                    <td>
                        @if ($provider->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('training.manage')
                            <a href="{{ route('admin.training.providers.edit', $provider) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.training.providers.destroy', $provider) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $provider->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No training providers yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $providers->links() }}</div>
@endsection
