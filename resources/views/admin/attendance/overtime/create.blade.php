@extends('layouts.admin')

@section('title', 'Request overtime')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Overtime', 'url' => route('admin.attendance.overtime.index')], ['label' => 'Request overtime']])

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.overtime.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="employee_id">Employee</label>
                    <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">Select an employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (int) old('employee_id') === $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="date">Date</label>
                        <input id="date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                               class="form-control @error('date') is-invalid @enderror" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="requested_hours">Hours</label>
                        <input id="requested_hours" type="number" step="0.5" min="0.5" max="24" name="requested_hours" value="{{ old('requested_hours') }}"
                               class="form-control @error('requested_hours') is-invalid @enderror" required>
                        @error('requested_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="reason">Reason</label>
                    <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('admin.attendance.overtime.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
