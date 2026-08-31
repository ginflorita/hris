@extends('layouts.admin')

@section('title', 'Edit payroll group')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Groups', 'url' => route('admin.payroll.payroll-groups.index')], ['label' => $payrollGroup->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.payroll-groups.update', $payrollGroup) }}">
                @csrf
                @method('PUT')

                @include('admin.payroll.payroll-groups._form-fields')

                <button type="submit" class="btn btn-primary">Save group</button>
                <a href="{{ route('admin.payroll.payroll-groups.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
