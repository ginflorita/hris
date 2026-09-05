@extends('layouts.admin')

@section('title', 'HR Report')

@php($breadcrumbs = [['label' => 'Reports', 'url' => route('admin.reports.index')], ['label' => 'HR Report']])

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
                    <div class="text-body-secondary small text-uppercase">Active Employees</div>
                    <div class="fs-3 fw-semibold">{{ $totalActive }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Archived Employees</div>
                    <div class="fs-3 fw-semibold">{{ $totalArchived }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">By Department</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @forelse ($byDepartment as $label => $count)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-body-secondary py-3">No active employees.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">By Employment Type</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @forelse ($byEmploymentType as $label => $count)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $label)) }}</td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-body-secondary py-3">No active employees.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">By Employment Status</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @forelse ($byStatus as $label => $count)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $label)) }}</td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-body-secondary py-3">No active employees.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
