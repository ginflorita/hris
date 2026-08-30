@extends('layouts.admin')

@section('title', 'Add job level')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Job Levels', 'url' => route('admin.organization.job-levels.index')], ['label' => 'Add job level']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.job-levels.store') }}">
                @csrf

                @include('admin.organization.job-levels._form-fields', ['jobLevel' => null])

                <button type="submit" class="btn btn-primary">Create job level</button>
                <a href="{{ route('admin.organization.job-levels.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
