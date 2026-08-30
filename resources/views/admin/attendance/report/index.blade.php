@extends('layouts.admin')

@section('title', 'Attendance Report')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Report']])

@section('content')
    <x-admin.attendance-subnav active="report" />

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
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div class="col-auto">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Days present</th>
                        <th>Days late</th>
                        <th>Days absent</th>
                        <th>Total late (min)</th>
                        <th>Total undertime (min)</th>
                        <th>Total overtime (min)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary as $row)
                        <tr>
                            <td>{{ $row['employee']->full_name }}</td>
                            <td>{{ $row['present'] }}</td>
                            <td>{{ $row['late'] }}</td>
                            <td>{{ $row['absent'] }}</td>
                            <td>{{ $row['late_minutes'] }}</td>
                            <td>{{ $row['undertime_minutes'] }}</td>
                            <td>{{ $row['overtime_minutes'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-3">No attendance records in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
