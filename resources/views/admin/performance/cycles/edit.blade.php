@extends('layouts.admin')

@section('title', 'Edit Performance Cycle')

@php($breadcrumbs = [['label' => 'Performance Cycles', 'url' => route('admin.performance.cycles.index')], ['label' => 'Edit']])

@section('content')
    <div class="card" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.performance.cycles.update', $cycle) }}">
                @csrf
                @method('PUT')
                @include('admin.performance.cycles._form-fields')
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.performance.cycles.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
