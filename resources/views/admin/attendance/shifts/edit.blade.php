@extends('layouts.admin')

@section('title', 'Edit shift')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Shifts', 'url' => route('admin.attendance.shifts.index')], ['label' => $shift->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.shifts.update', $shift) }}">
                @csrf
                @method('PUT')

                @include('admin.attendance.shifts._form-fields')

                <button type="submit" class="btn btn-primary">Save shift</button>
                <a href="{{ route('admin.attendance.shifts.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
