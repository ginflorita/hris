@extends('layouts.admin')

@section('title', 'Reports')

@php($breadcrumbs = [['label' => 'Reports']])

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Reports</h1>
        <p class="text-body-secondary mb-0">Workforce, attendance, leave, and payroll reporting in one place.</p>
    </div>

    <div class="row g-3">
        @foreach ([
            ['label' => 'HR Reports', 'description' => 'Headcount by department, employment type, and status.', 'route' => 'admin.reports.hr.index', 'can' => ['reports.view']],
            ['label' => 'Attendance Reports', 'description' => 'Presence, lateness, undertime, and overtime by employee.', 'route' => 'admin.attendance.report.index', 'can' => ['attendance.view']],
            ['label' => 'Leave Reports', 'description' => 'Leave usage and balances by employee and leave type.', 'route' => 'admin.leave.report.index', 'can' => ['leave.view']],
            ['label' => 'Payroll Reports', 'description' => 'Payroll cost, deductions, and contributions by period.', 'route' => 'admin.reports.payroll.index', 'can' => ['payroll.view']],
            ['label' => 'Recruitment Reports', 'description' => 'Application pipeline funnel and requisition status.', 'route' => 'admin.reports.recruitment.index', 'can' => ['recruitment.view']],
            ['label' => 'Performance Reports', 'description' => 'Average ratings and goal completion by cycle.', 'route' => 'admin.reports.performance.index', 'can' => ['performance.view']],
            ['label' => 'Training Reports', 'description' => 'Enrollment, completion, and certificate status.', 'route' => 'admin.reports.training.index', 'can' => ['training.view']],
            ['label' => 'Analytics', 'description' => 'Cross-module workforce metrics at a glance.', 'route' => 'admin.reports.analytics.index', 'can' => ['reports.view']],
        ] as $report)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h6">{{ $report['label'] }}</h2>
                        <p class="text-body-secondary small flex-grow-1">{{ $report['description'] }}</p>
                        @if (isset($report['route']) && Route::has($report['route']) && auth()->user()?->can(...$report['can']))
                            <a href="{{ route($report['route']) }}" class="btn btn-outline-primary btn-sm align-self-start">View</a>
                        @else
                            <span class="badge text-bg-secondary align-self-start">Coming soon</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
