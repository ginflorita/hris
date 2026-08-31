@extends('layouts.admin')

@section('title', 'New Job Requisition')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Requisitions', 'url' => route('admin.recruitment.requisitions.index')], ['label' => 'New']])

@section('content')
    <x-admin.recruitment-subnav active="requisitions" />

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.recruitment.requisitions.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="company_id">Company</label>
                    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        <option value="">Select a company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" {{ (int) old('company_id') === $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">None</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ (int) old('department_id') === $department->id ? 'selected' : '' }}>
                                {{ $department->name }} ({{ $department->company->name }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Must belong to the selected company.</div>
                    @error('department_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="position_id">Position</label>
                    <select id="position_id" name="position_id" class="form-select @error('position_id') is-invalid @enderror">
                        <option value="">None</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" {{ (int) old('position_id') === $position->id ? 'selected' : '' }}>
                                {{ $position->title }} ({{ $position->company->name }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Must belong to the selected company.</div>
                    @error('position_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="openings_count">Openings</label>
                        <input type="number" id="openings_count" name="openings_count" min="1" class="form-control @error('openings_count') is-invalid @enderror" value="{{ old('openings_count', 1) }}" required>
                        @error('openings_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="target_start_date">Target Start Date</label>
                        <input type="date" id="target_start_date" name="target_start_date" class="form-control @error('target_start_date') is-invalid @enderror" value="{{ old('target_start_date') }}">
                        @error('target_start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="justification">Justification</label>
                    <textarea id="justification" name="justification" rows="3" class="form-control @error('justification') is-invalid @enderror">{{ old('justification') }}</textarea>
                    @error('justification')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit Requisition</button>
                <a href="{{ route('admin.recruitment.requisitions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
