@extends('layouts.admin')

@section('title', 'Add job grade')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Job Grades', 'url' => route('admin.organization.job-grades.index')], ['label' => 'Add job grade']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.job-grades.store') }}">
                @csrf

                @include('admin.organization.job-grades._form-fields', ['jobGrade' => null])

                <button type="submit" class="btn btn-primary">Create job grade</button>
                <a href="{{ route('admin.organization.job-grades.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
