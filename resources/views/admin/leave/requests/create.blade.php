@extends('layouts.admin')

@section('title', 'Submit leave request')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Requests', 'url' => route('admin.leave.requests.index')], ['label' => 'Submit request']])

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leave.requests.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="employee_id">Employee</label>
                    <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">Select an employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" data-company="{{ $employee->company_id }}" {{ (int) old('employee_id') === $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }} ({{ $employee->employee_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="leave_type_id">Leave type</label>
                    <select id="leave_type_id" name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                        <option value="">Select a leave type</option>
                        @foreach ($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}" {{ (int) old('leave_type_id') === $leaveType->id ? 'selected' : '' }}>
                                {{ $leaveType->name }} ({{ $leaveType->company->name }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Must belong to the selected employee's company.</div>
                    @error('leave_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="start_date">Start date</label>
                        <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}"
                               class="form-control @error('start_date') is-invalid @enderror" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="end_date">End date</label>
                        <input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}"
                               class="form-control @error('end_date') is-invalid @enderror" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="reason">Reason</label>
                    <textarea id="reason" name="reason" rows="2" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('admin.leave.requests.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
