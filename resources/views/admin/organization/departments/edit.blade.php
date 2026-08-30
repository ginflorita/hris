@extends('layouts.admin')

@section('title', 'Edit department')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Departments', 'url' => route('admin.organization.departments.index')], ['label' => $department->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.departments.update', $department) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.departments._form-fields')

                <button type="submit" class="btn btn-primary">Save department</button>
                <a href="{{ route('admin.organization.departments.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
