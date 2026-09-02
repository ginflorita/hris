@extends('layouts.admin')

@section('title', 'Add training course')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Courses', 'url' => route('admin.training.courses.index')], ['label' => 'Add course']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.courses.store') }}">
                @csrf

                @include('admin.training.courses._form-fields', ['course' => null])

                <button type="submit" class="btn btn-primary">Create course</button>
                <a href="{{ route('admin.training.courses.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
