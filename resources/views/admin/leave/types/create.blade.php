@extends('layouts.admin')

@section('title', 'Add leave type')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Leave Types', 'url' => route('admin.leave.types.index')], ['label' => 'Add leave type']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leave.types.store') }}">
                @csrf

                @include('admin.leave.types._form-fields', ['leaveType' => null])

                <button type="submit" class="btn btn-primary">Create leave type</button>
                <a href="{{ route('admin.leave.types.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
