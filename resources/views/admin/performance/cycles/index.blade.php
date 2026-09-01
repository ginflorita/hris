@extends('layouts.admin')

@section('title', 'Performance Cycles')

@php($breadcrumbs = [['label' => 'Performance Cycles']])

@section('content')
    <x-admin.resource-index
        :create-url="auth()->user()->can('performance.manage') ? route('admin.performance.cycles.create') : null"
        create-label="New cycle"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Period</th>
                <th>Goals</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cycles as $cycle)
                <tr>
                    <td>{{ $cycle->name }}</td>
                    <td>{{ $cycle->company->name }}</td>
                    <td>{{ $cycle->start_date->format('M d, Y') }} &ndash; {{ $cycle->end_date->format('M d, Y') }}</td>
                    <td>{{ $cycle->goals_count }}</td>
                    <td>
                        @if ($cycle->status->value === 'active')
                            <span class="badge text-bg-success">Active</span>
                        @elseif ($cycle->status->value === 'closed')
                            <span class="badge text-bg-secondary">Closed</span>
                        @else
                            <span class="badge text-bg-warning">Draft</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('performance.manage')
                            @if ($cycle->status->value === 'draft')
                                <a href="{{ route('admin.performance.cycles.edit', $cycle) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.performance.cycles.activate', $cycle) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                </form>
                            @elseif ($cycle->status->value === 'active')
                                <form method="POST" action="{{ route('admin.performance.cycles.close', $cycle) }}" class="d-inline"
                                      onsubmit="return confirm('Close this cycle?');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Close</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No performance cycles yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $cycles->links() }}</div>
@endsection
