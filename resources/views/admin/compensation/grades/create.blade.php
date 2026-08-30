@extends('layouts.admin')

@section('title', 'Add salary grade')

@php($breadcrumbs = [['label' => 'Compensation'], ['label' => 'Salary Grades', 'url' => route('admin.compensation.grades.index')], ['label' => 'Add grade']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.compensation.grades.store') }}">
                @csrf

                @include('admin.compensation.grades._form-fields', ['salaryGrade' => null])

                <button type="submit" class="btn btn-primary">Create grade</button>
                <a href="{{ route('admin.compensation.grades.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
