@extends('layouts.portal')

@section('title', 'Requests')

@php($breadcrumbs = [['label' => 'Requests']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <h1 class="h4 mb-3">My Requests</h1>
        <p class="text-body-secondary">All of your leave, overtime, attendance correction, and COE requests in one place.</p>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $item)
                            <tr>
                                <td>{{ $item->type }}</td>
                                <td>{{ $item->date->format('M d, Y') }}</td>
                                <td>{{ $item->detail }}</td>
                                <td>
                                    @php($statusLabel = ucwords(str_replace('_', ' ', $item->status)))
                                    @if (in_array($item->status, ['approved', 'published']))
                                        <span class="badge text-bg-success">{{ $statusLabel }}</span>
                                    @elseif (in_array($item->status, ['rejected', 'cancelled']))
                                        <span class="badge text-bg-danger">{{ $statusLabel }}</span>
                                    @else
                                        <span class="badge text-bg-warning">{{ $statusLabel }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ $item->link }}" class="btn btn-sm btn-outline-secondary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-body-secondary py-3">No requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless
@endsection
