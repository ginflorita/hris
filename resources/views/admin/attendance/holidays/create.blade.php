@extends('layouts.admin')

@section('title', 'Add holiday')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Holidays', 'url' => route('admin.attendance.holidays.index')], ['label' => 'Add holiday']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.holidays.store') }}">
                @csrf

                @include('admin.attendance.holidays._form-fields', ['holiday' => null])

                <button type="submit" class="btn btn-primary">Create holiday</button>
                <a href="{{ route('admin.attendance.holidays.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
