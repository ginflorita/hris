@extends('layouts.admin')

@section('title', 'Edit employee')

@php($breadcrumbs = [['label' => 'Employees', 'url' => route('admin.employees.index')], ['label' => $employee->full_name, 'url' => route('admin.employees.show', $employee)], ['label' => 'Edit']])

@section('content')
    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
                @csrf
                @method('PUT')

                @include('admin.employees._form-fields')

                <button type="submit" class="btn btn-primary">Save employee</button>
                <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
