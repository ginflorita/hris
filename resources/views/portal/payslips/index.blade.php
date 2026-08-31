@extends('layouts.portal')

@section('title', 'My Payslips')

@php($breadcrumbs = [['label' => 'My Payslips']])

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">My Payslips</h1>
        <p class="text-body-secondary mb-0">Payslips appear here once your employer publishes the pay period.</p>
    </div>

    @unless ($linked)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Pay period</th>
                            <th>Pay date</th>
                            <th class="text-end">Net pay</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payrollItems as $item)
                            <tr>
                                <td>{{ $item->payrollPeriod->name }}</td>
                                <td>{{ $item->payrollPeriod->pay_date->format('M d, Y') }}</td>
                                <td class="text-end">{{ number_format($item->net_pay, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('portal.payslips.show', $item) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('portal.payslips.download', $item) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-secondary py-3">No payslips published yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless
@endsection
