@extends('layouts.admin')

@section('title', 'Workforce Analytics')

@php($breadcrumbs = [['label' => 'Reports', 'url' => route('admin.reports.index')], ['label' => 'Workforce Analytics']])

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="company_id" class="form-select" onchange="this.form.submit()">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ (int) $companyId === $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="row g-3">
        @foreach ([
            ['label' => 'Active Employees', 'value' => $activeEmployees],
            ['label' => 'Open Postings', 'value' => $openPostings],
            ['label' => 'Pending Requisitions', 'value' => $pendingRequisitions],
            ['label' => 'Pending Leave Requests', 'value' => $pendingLeaveRequests],
            ['label' => 'Pending Overtime Requests', 'value' => $pendingOvertimeRequests],
            ['label' => 'Average Performance Rating', 'value' => $averagePerformanceRating ?? '—'],
            ['label' => 'Training Completion Rate', 'value' => $trainingCompletionRate !== null ? $trainingCompletionRate.'%' : '—'],
        ] as $tile)
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">{{ $tile['label'] }}</div>
                        <div class="fs-3 fw-semibold">{{ $tile['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
