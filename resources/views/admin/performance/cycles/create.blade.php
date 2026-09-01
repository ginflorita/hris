@extends('layouts.admin')

@section('title', 'New Performance Cycle')

@php($breadcrumbs = [['label' => 'Performance Cycles', 'url' => route('admin.performance.cycles.index')], ['label' => 'New']])

@section('content')
    <div class="card" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.performance.cycles.store') }}">
                @csrf
                @include('admin.performance.cycles._form-fields')
                <button type="submit" class="btn btn-primary">Create Cycle</button>
                <a href="{{ route('admin.performance.cycles.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
