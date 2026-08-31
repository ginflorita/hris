@extends('layouts.admin')

@section('title', 'Add payroll period')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Periods', 'url' => route('admin.payroll.payroll-periods.index')], ['label' => 'Add period']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.payroll-periods.store') }}">
                @csrf

                @include('admin.payroll.payroll-periods._form-fields', ['payrollPeriod' => null])

                <button type="submit" class="btn btn-primary">Create period</button>
                <a href="{{ route('admin.payroll.payroll-periods.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
