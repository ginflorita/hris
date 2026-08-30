@extends('layouts.admin')

@section('title', 'Leave Calendar')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Calendar']])

@section('content')
    <x-admin.leave-subnav active="calendar" />

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="company_id" class="form-select">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ (int) $companyId === $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <input type="month" name="month" value="{{ substr($month, 0, 7) }}" class="form-control">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">View</button>
        </div>
    </form>

    <h2 class="h5 mb-3">{{ $monthLabel }}</h2>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave type</th>
                        <th>Dates</th>
                        <th>Days</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaveRequests as $leaveRequest)
                        <tr>
                            <td>{{ $leaveRequest->employee->full_name }}</td>
                            <td>{{ $leaveRequest->leaveType->name }}</td>
                            <td>{{ $leaveRequest->start_date->format('M d') }} – {{ $leaveRequest->end_date->format('M d, Y') }}</td>
                            <td>{{ $leaveRequest->days_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-3">No approved leave this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
