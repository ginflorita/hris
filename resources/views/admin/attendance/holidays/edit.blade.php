@extends('layouts.admin')

@section('title', 'Edit holiday')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Holidays', 'url' => route('admin.attendance.holidays.index')], ['label' => $holiday->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.holidays.update', $holiday) }}">
                @csrf
                @method('PUT')

                @include('admin.attendance.holidays._form-fields')

                <button type="submit" class="btn btn-primary">Save holiday</button>
                <a href="{{ route('admin.attendance.holidays.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
