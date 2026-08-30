@extends('layouts.admin')

@section('title', 'Add schedule')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Schedules', 'url' => route('admin.attendance.schedules.index')], ['label' => 'Add schedule']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.schedules.store') }}">
                @csrf

                @include('admin.attendance.schedules._form-fields', ['schedule' => null])

                <button type="submit" class="btn btn-primary">Create schedule</button>
                <a href="{{ route('admin.attendance.schedules.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
