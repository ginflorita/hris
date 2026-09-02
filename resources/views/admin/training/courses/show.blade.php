@extends('layouts.admin')

@section('title', $course->name)

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Courses', 'url' => route('admin.training.courses.index')], ['label' => $course->name]])

@section('content')
    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $course->name }}</h5>
                    <div class="text-body-secondary">
                        {{ $course->company->name }}
                        @if ($course->provider) &middot; {{ $course->provider->name }} @endif
                        @if ($course->duration_hours) &middot; {{ $course->duration_hours }}h @endif
                    </div>
                </div>
                @can('training.manage')
                    <a href="{{ route('admin.training.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                @endcan
            </div>
            @if ($course->description)
                <p class="mt-3 mb-0">{{ $course->description }}</p>
            @endif
        </div>
    </div>

    <h6>Sessions</h6>

    @can('training.manage')
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSessionModal">Add Session</button>
        </div>
    @endcan

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Dates</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->start_date->format('M d, Y') }} &ndash; {{ $session->end_date->format('M d, Y') }}</td>
                            <td>{{ $session->location ?? '—' }}</td>
                            <td>{{ $session->capacity ?? '—' }}</td>
                            <td>{{ $session->cost !== null ? number_format((float) $session->cost, 2) : '—' }}</td>
                            <td>
                                @if ($session->status->value === 'completed')
                                    <span class="badge text-bg-success">Completed</span>
                                @elseif ($session->status->value === 'cancelled')
                                    <span class="badge text-bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge text-bg-primary">Scheduled</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.training.courses.sessions.show', [$course, $session]) }}" class="btn btn-sm btn-outline-secondary">Roster</a>
                                @can('training.manage')
                                    @if ($session->status->value === 'scheduled')
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSessionModal{{ $session->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.training.courses.sessions.complete', [$course, $session]) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Complete</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.training.courses.sessions.cancel', [$course, $session]) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-3">No sessions scheduled yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('training.manage')
        <div class="modal fade" id="addSessionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.training.courses.sessions.store', $course) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Session</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('admin.training.courses._session-fields', ['session' => null])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Session</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($sessions as $session)
            @if ($session->status->value === 'scheduled')
                <div class="modal fade" id="editSessionModal{{ $session->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.training.courses.sessions.update', [$course, $session]) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Session</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @include('admin.training.courses._session-fields', ['session' => $session])
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endcan
@endsection
