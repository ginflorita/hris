@extends('layouts.admin')

@section('title', 'Training Report')

@php($breadcrumbs = [['label' => 'Reports', 'url' => route('admin.reports.index')], ['label' => 'Training Report']])

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

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Enrollments</div>
                    <div class="fs-3 fw-semibold">{{ $totalEnrollments }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Completion Rate</div>
                    <div class="fs-3 fw-semibold">{{ $completionRate !== null ? $completionRate.'%' : '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Certificates Issued</div>
                    <div class="fs-3 fw-semibold">{{ $certificatesIssued }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Expiring Within 30 Days</div>
                    <div class="fs-3 fw-semibold">{{ $certificatesExpiringSoon }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Enrollments by Status</div>
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <tbody>
                    @foreach ($byStatus as $row)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $row['status']->value)) }}</td>
                            <td class="text-end">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
