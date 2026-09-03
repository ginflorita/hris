@extends('layouts.admin')

@section('title', 'Recruitment Report')

@php($breadcrumbs = [['label' => 'Reports', 'url' => route('admin.reports.index')], ['label' => 'Recruitment Report']])

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
                    <div class="text-body-secondary small text-uppercase">Total Applications</div>
                    <div class="fs-3 fw-semibold">{{ $totalApplications }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Open Postings</div>
                    <div class="fs-3 fw-semibold">{{ $openPostings }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Hired</div>
                    <div class="fs-3 fw-semibold">{{ $hiredCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Application Pipeline</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @foreach ($pipeline as $row)
                                <tr>
                                    <td>{{ $row['status']->label() }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Requisitions by Status</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @foreach ($requisitionsByStatus as $row)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $row['status']->value)) }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
