@extends('layouts.admin')

@section('title', 'Add shift')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Shifts', 'url' => route('admin.attendance.shifts.index')], ['label' => 'Add shift']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.shifts.store') }}">
                @csrf

                @include('admin.attendance.shifts._form-fields', ['shift' => null])

                <button type="submit" class="btn btn-primary">Create shift</button>
                <a href="{{ route('admin.attendance.shifts.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
