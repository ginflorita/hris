@extends('layouts.admin')

@section('title', 'Add department')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Departments', 'url' => route('admin.organization.departments.index')], ['label' => 'Add department']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.departments.store') }}">
                @csrf

                @include('admin.organization.departments._form-fields', ['department' => null])

                <button type="submit" class="btn btn-primary">Create department</button>
                <a href="{{ route('admin.organization.departments.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
