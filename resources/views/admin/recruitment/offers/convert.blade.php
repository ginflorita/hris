@extends('layouts.admin')

@section('title', 'Convert '.$application->applicant->full_name.' to Employee')

@php($breadcrumbs = [
    ['label' => 'Recruitment'],
    ['label' => 'Applications', 'url' => route('admin.recruitment.applications.index')],
    ['label' => $application->applicant->full_name, 'url' => route('admin.recruitment.applications.show', $application)],
    ['label' => 'Convert to Employee'],
])

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <h1 class="h4 mb-3">Convert {{ $application->applicant->full_name }} to Employee</h1>

    <div class="card mb-3">
        <div class="card-header">Offer Summary</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Company</dt>
                <dd class="col-sm-9">{{ $application->jobPosting->company->name }}</dd>
                <dt class="col-sm-3">Department</dt>
                <dd class="col-sm-9">{{ $offer->department?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Position</dt>
                <dd class="col-sm-9">{{ $offer->position?->title ?? '—' }}</dd>
                <dt class="col-sm-3">Employment Type</dt>
                <dd class="col-sm-9">{{ ucwords(str_replace('_', ' ', $offer->employment_type->value)) }}</dd>
                <dt class="col-sm-3">Salary</dt>
                <dd class="col-sm-9">{{ number_format($offer->offered_salary, 2) }}</dd>
                <dt class="col-sm-3">Start Date</dt>
                <dd class="col-sm-9">{{ $offer->start_date->format('M d, Y') }}</dd>
            </dl>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.recruitment.applications.offers.convert', [$application, $offer]) }}">
        @csrf
        <div class="card">
            <div class="card-header">Employee Record</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" value="{{ $application->applicant->first_name }}" disabled>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" value="{{ $application->applicant->last_name }}" disabled>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="{{ $application->applicant->email }}" disabled>
                    </div>
                </div>
                <p class="text-body-secondary small">Name, email, and mobile come from the applicant's profile. To correct them, edit the applicant record first.</p>

                <div class="mb-3">
                    <label class="form-label" for="employee_number">Employee Number</label>
                    <input id="employee_number" type="text" name="employee_number" value="{{ old('employee_number') }}"
                           class="form-control @error('employee_number') is-invalid @enderror" required>
                    @error('employee_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label" for="middle_name">Middle Name</label>
                        <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label" for="suffix">Suffix</label>
                        <input id="suffix" type="text" name="suffix" value="{{ old('suffix') }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label" for="preferred_name">Preferred Name</label>
                        <input id="preferred_name" type="text" name="preferred_name" value="{{ old('preferred_name') }}" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label" for="birth_date">Birth Date</label>
                        <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}"
                               class="form-control @error('birth_date') is-invalid @enderror">
                        @error('birth_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label" for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Select</option>
                            @foreach (\App\Enums\Gender::cases() as $case)
                                <option value="{{ $case->value }}" {{ old('gender') === $case->value ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $case->value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label" for="civil_status">Civil Status</label>
                        <select id="civil_status" name="civil_status" class="form-select">
                            <option value="">Select</option>
                            @foreach (\App\Enums\CivilStatus::cases() as $case)
                                <option value="{{ $case->value }}" {{ old('civil_status') === $case->value ? 'selected' : '' }}>
                                    {{ ucfirst($case->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="nationality">Nationality</label>
                    <input id="nationality" type="text" name="nationality" value="{{ old('nationality') }}" class="form-control">
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.recruitment.applications.show', $application) }}" class="btn btn-link">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Employee</button>
            </div>
        </div>
    </form>
@endsection
