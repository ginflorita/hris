@extends('layouts.admin')

@section('title', 'Edit tax table')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Tax Tables', 'url' => route('admin.payroll.tax-tables.index')], ['label' => $taxTable->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.tax-tables.update', $taxTable) }}">
                @csrf
                @method('PUT')

                @include('admin.payroll.tax-tables._form-fields')

                <button type="submit" class="btn btn-primary">Save table</button>
                <a href="{{ route('admin.payroll.tax-tables.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
