@extends('layouts.admin')

@section('title', 'Edit leave type')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Leave Types', 'url' => route('admin.leave.types.index')], ['label' => $leaveType->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leave.types.update', $leaveType) }}">
                @csrf
                @method('PUT')

                @include('admin.leave.types._form-fields')

                <button type="submit" class="btn btn-primary">Save leave type</button>
                <a href="{{ route('admin.leave.types.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
