@extends('layouts.admin')

@section('title', 'Performance Report')

@php($breadcrumbs = [['label' => 'Reports', 'url' => route('admin.reports.index')], ['label' => 'Performance Report']])

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
        <div class="col-auto">
            <select name="performance_cycle_id" class="form-select" onchange="this.form.submit()">
                @forelse ($cycles as $cycle)
                    <option value="{{ $cycle->id }}" {{ $selectedCycle?->id === $cycle->id ? 'selected' : '' }}>
                        {{ $cycle->name }} ({{ ucwords(str_replace('_', ' ', $cycle->status->value)) }})
                    </option>
                @empty
                    <option value="">No performance cycles</option>
                @endforelse
            </select>
        </div>
    </form>

    @if (! $selectedCycle)
        <div class="alert alert-secondary">No performance cycles to report on{{ $companyId ? ' for this company' : '' }}.</div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Reviews</div>
                        <div class="fs-3 fw-semibold">{{ $totalReviews }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Average Rating</div>
                        <div class="fs-3 fw-semibold">{{ $averageRating ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Goals</div>
                        <div class="fs-3 fw-semibold">{{ $totalGoals }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Goal Completion</div>
                        <div class="fs-3 fw-semibold">{{ $goalCompletionRate !== null ? $goalCompletionRate.'%' : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">Reviews by Type</div>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <tbody>
                                @foreach ($byReviewType as $row)
                                    <tr>
                                        <td>{{ ucfirst($row['type']->value) }}</td>
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
                    <div class="card-header">Recent Cycles — Average Rating</div>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <tbody>
                                @forelse ($recentCycles as $row)
                                    <tr>
                                        <td>{{ $row['cycle']->name }}</td>
                                        <td class="text-end">{{ $row['averageRating'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-body-secondary py-3">No cycles yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
