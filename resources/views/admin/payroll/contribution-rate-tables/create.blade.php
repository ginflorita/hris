@extends('layouts.admin')

@section('title', 'Add contribution rate table')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Contribution Rate Tables', 'url' => route('admin.payroll.contribution-rate-tables.index')], ['label' => 'Add table']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.contribution-rate-tables.store') }}">
                @csrf

                @include('admin.payroll.contribution-rate-tables._form-fields', ['contributionRateTable' => null])

                <button type="submit" class="btn btn-primary">Create table</button>
                <a href="{{ route('admin.payroll.contribution-rate-tables.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
