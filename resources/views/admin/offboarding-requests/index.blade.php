@extends('layouts.admin')

@section('title', 'Offboarding Requests')

@php($breadcrumbs = [['label' => 'Offboarding Requests']])

@section('content')
    <x-admin.resource-index>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Company</th>
                <th>Resignation Date</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $offboardingRequest)
                <tr>
                    <td>{{ $offboardingRequest->employee->full_name }}</td>
                    <td>{{ $offboardingRequest->employee->company->name }}</td>
                    <td>{{ $offboardingRequest->resignation_date->format('M d, Y') }}</td>
                    <td>
                        @if ($offboardingRequest->status->value === 'cancelled')
                            <span class="badge text-bg-secondary">{{ $offboardingRequest->status->label() }}</span>
                        @elseif ($offboardingRequest->status->value === 'separated')
                            <span class="badge text-bg-success">{{ $offboardingRequest->status->label() }}</span>
                        @else
                            <span class="badge text-bg-primary">{{ $offboardingRequest->status->label() }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.employees.show', $offboardingRequest->employee) }}#offboarding" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No offboarding requests yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $requests->links() }}</div>
@endsection
