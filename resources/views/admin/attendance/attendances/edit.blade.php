@extends('layouts.admin')

@section('title', 'Correct attendance')

@php($breadcrumbs = [['label' => 'Attendance', 'url' => route('admin.attendance.attendances.index')], ['label' => 'Correct attendance']])

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <h5 class="mb-3">{{ $attendance->employee->full_name }} — {{ $attendance->date->format('M d, Y') }}</h5>

            @if ($attendance->correctionLogs->isNotEmpty())
                <div class="alert alert-warning py-2">
                    This record has been corrected before.
                    <ul class="mb-0 small">
                        @foreach ($attendance->correctionLogs as $log)
                            <li>{{ $log->field }}: {{ $log->old_value ?? '—' }} → {{ $log->new_value ?? '—' }} ({{ $log->reason }})</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.attendance.attendances.update', $attendance) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="time_in">Time in</label>
                        <input id="time_in" type="time" name="time_in" value="{{ old('time_in', $attendance->time_in?->format('H:i')) }}"
                               class="form-control @error('time_in') is-invalid @enderror">
                        @error('time_in')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="time_out">Time out</label>
                        <input id="time_out" type="time" name="time_out" value="{{ old('time_out', $attendance->time_out?->format('H:i')) }}"
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
                            <option value="{{ $case->value }}" {{ old('status', $attendance->status->value) === $case->value ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $case->value)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="reason">Reason for correction</label>
                    <textarea id="reason" name="reason" rows="2" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Save correction</button>
                <a href="{{ route('admin.attendance.attendances.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
