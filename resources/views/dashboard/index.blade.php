@extends('layouts.admin')

@section('title', 'Dashboard')

@php($breadcrumbs = [['label' => 'Dashboard']])

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Welcome to HRIS</h1>
        <p class="text-body-secondary mb-0">
            Project foundation and UI shell are in place. Modules below light up as each phase is built —
            see <code>CLAUDE.md</code> and <code>docs/HRIS_Blueprint.md</code> for the roadmap.
        </p>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Employees', 'value' => '—'],
            ['label' => 'Pending Leave Requests', 'value' => '—'],
            ['label' => 'Open Payroll Run', 'value' => '—'],
            ['label' => 'Pending Approvals', 'value' => '—'],
        ] as $stat)
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-semibold">{{ $stat['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">Build status</div>
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Phase</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1 — Project Foundation</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>2 — UI/UX Foundation</td>
                        <td><span class="badge text-bg-warning">In progress</span></td>
                    </tr>
                    <tr>
                        <td>3 — Authentication</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>4 — RBAC &amp; Authorization</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>5 — Organization</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>6 — Employee Core HR</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>7 — Employee Lifecycle</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>8 — Attendance &amp; Scheduling</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>9 — Leave Management</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>10 — Compensation</td>
                        <td><span class="badge text-bg-success">Done</span></td>
                    </tr>
                    <tr>
                        <td>11–18 — Payroll Engine through Production</td>
                        <td><span class="badge text-bg-secondary">Not started</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
