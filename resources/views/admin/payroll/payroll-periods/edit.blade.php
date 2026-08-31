@extends('layouts.admin')

@section('title', 'Edit payroll period')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Periods', 'url' => route('admin.payroll.payroll-periods.index')], ['label' => $payrollPeriod->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.payroll-periods.update', $payrollPeriod) }}">
                @csrf
                @method('PUT')

                @include('admin.payroll.payroll-periods._form-fields')

                <button type="submit" class="btn btn-primary">Save period</button>
                <a href="{{ route('admin.payroll.payroll-periods.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
