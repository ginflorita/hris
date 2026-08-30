@extends('layouts.admin')

@section('title', 'Edit salary grade')

@php($breadcrumbs = [['label' => 'Compensation'], ['label' => 'Salary Grades', 'url' => route('admin.compensation.grades.index')], ['label' => $salaryGrade->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.compensation.grades.update', $salaryGrade) }}">
                @csrf
                @method('PUT')

                @include('admin.compensation.grades._form-fields')

                <button type="submit" class="btn btn-primary">Save grade</button>
                <a href="{{ route('admin.compensation.grades.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
