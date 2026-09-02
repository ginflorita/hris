@extends('layouts.admin')

@section('title', 'Edit training course')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Courses', 'url' => route('admin.training.courses.index')], ['label' => $course->name, 'url' => route('admin.training.courses.show', $course)], ['label' => 'Edit']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.courses.update', $course) }}">
                @csrf
                @method('PUT')

                @include('admin.training.courses._form-fields')

                <button type="submit" class="btn btn-primary">Save course</button>
                <a href="{{ route('admin.training.courses.show', $course) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
