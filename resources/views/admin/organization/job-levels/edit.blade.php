@extends('layouts.admin')

@section('title', 'Edit job level')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Job Levels', 'url' => route('admin.organization.job-levels.index')], ['label' => $jobLevel->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.job-levels.update', $jobLevel) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.job-levels._form-fields')

                <button type="submit" class="btn btn-primary">Save job level</button>
                <a href="{{ route('admin.organization.job-levels.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
