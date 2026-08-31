@extends('layouts.admin')

@section('title', 'Add payroll group')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Groups', 'url' => route('admin.payroll.payroll-groups.index')], ['label' => 'Add group']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.payroll.payroll-groups.store') }}">
                @csrf

                @include('admin.payroll.payroll-groups._form-fields', ['payrollGroup' => null])

                <button type="submit" class="btn btn-primary">Create group</button>
                <a href="{{ route('admin.payroll.payroll-groups.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
