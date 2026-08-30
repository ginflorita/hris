@extends('layouts.admin')

@section('title', 'Edit job grade')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Job Grades', 'url' => route('admin.organization.job-grades.index')], ['label' => $jobGrade->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.job-grades.update', $jobGrade) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.job-grades._form-fields')

                <button type="submit" class="btn btn-primary">Save job grade</button>
                <a href="{{ route('admin.organization.job-grades.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
