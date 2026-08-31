@extends('layouts.admin')

@section('title', 'Add tax table')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Tax Tables', 'url' => route('admin.payroll.tax-tables.index')], ['label' => 'Add table']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.tax-tables.store') }}">
                @csrf

                @include('admin.payroll.tax-tables._form-fields', ['taxTable' => null])

                <button type="submit" class="btn btn-primary">Create table</button>
                <a href="{{ route('admin.payroll.tax-tables.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
