@extends('layouts.admin')

@section('title', 'Edit contribution rate table')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Contribution Rate Tables', 'url' => route('admin.payroll.contribution-rate-tables.index')], ['label' => $contributionRateTable->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.contribution-rate-tables.update', $contributionRateTable) }}">
                @csrf
                @method('PUT')

                @include('admin.payroll.contribution-rate-tables._form-fields')

                <button type="submit" class="btn btn-primary">Save table</button>
                <a href="{{ route('admin.payroll.contribution-rate-tables.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
