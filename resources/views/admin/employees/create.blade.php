@extends('layouts.admin')

@section('title', 'Add employee')

@php($breadcrumbs = [['label' => 'Employees', 'url' => route('admin.employees.index')], ['label' => 'Add employee']])

@section('content')
    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.employees.store') }}">
                @csrf

                @include('admin.employees._form-fields', ['employee' => null])

                <button type="submit" class="btn btn-primary">Create employee</button>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
