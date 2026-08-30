@extends('layouts.admin')

@section('title', 'Edit schedule')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Schedules', 'url' => route('admin.attendance.schedules.index')], ['label' => $schedule->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.schedules.update', $schedule) }}">
                @csrf
                @method('PUT')

                @include('admin.attendance.schedules._form-fields')

                <button type="submit" class="btn btn-primary">Save schedule</button>
                <a href="{{ route('admin.attendance.schedules.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
