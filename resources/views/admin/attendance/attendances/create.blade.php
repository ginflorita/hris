@extends('layouts.admin')

@section('title', 'Record attendance')

@php($breadcrumbs = [['label' => 'Attendance', 'url' => route('admin.attendance.attendances.index')], ['label' => 'Record attendance']])

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.attendance.attendances.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="employee_id">Employee</label>
                    <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">Select an employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (int) old('employee_id') === $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }} ({{ $employee->employee_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="date">Date</label>
                    <input id="date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                           class="form-control @error('date') is-invalid @enderror" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="time_in">Time in</label>
                        <input id="time_in" type="time" name="time_in" value="{{ old('time_in') }}"
                               class="form-control @error('time_in') is-invalid @enderror">
                        @error('time_in')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="time_out">Time out</label>
                        <input id="time_out" type="time" name="time_out" value="{{ old('time_out') }}"
                               class="form-control @error('time_out') is-invalid @enderror">
                        @error('time_out')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach (\App\Enums\AttendanceStatus::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('status') === $case->value ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $case->value)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.attendance.attendances.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
