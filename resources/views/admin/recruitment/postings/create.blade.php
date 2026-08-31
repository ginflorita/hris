@extends('layouts.admin')

@section('title', 'New Job Posting')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Postings', 'url' => route('admin.recruitment.postings.index')], ['label' => 'New']])

@section('content')
    <x-admin.recruitment-subnav active="postings" />

    @if ($requisitions->isEmpty())
        <div class="alert alert-warning">
            No approved requisitions yet. <a href="{{ route('admin.recruitment.requisitions.create') }}">Submit one</a> and have it approved before creating a posting.
        </div>
    @else
        <div class="card" style="max-width: 640px;">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.recruitment.postings.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="job_requisition_id">Requisition</label>
                        <select id="job_requisition_id" name="job_requisition_id" class="form-select @error('job_requisition_id') is-invalid @enderror" required>
                            <option value="">Select an approved requisition</option>
                            @foreach ($requisitions as $requisition)
                                <option value="{{ $requisition->id }}" {{ (int) old('job_requisition_id') === $requisition->id ? 'selected' : '' }}>
                                    {{ $requisition->company->name }} — {{ $requisition->position->title ?? $requisition->department->name ?? 'Requisition #'.$requisition->id }}
                                    ({{ $requisition->openings_count }} opening(s))
                                </option>
                            @endforeach
                        </select>
                        @error('job_requisition_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" id="is_internal" name="is_internal" value="1" class="form-check-input" {{ old('is_internal') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_internal">Internal posting only</label>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="closes_at">Closes At</label>
                            <input type="date" id="closes_at" name="closes_at" class="form-control @error('closes_at') is-invalid @enderror" value="{{ old('closes_at') }}">
                            @error('closes_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Posting</button>
                    <a href="{{ route('admin.recruitment.postings.index') }}" class="btn btn-link">Cancel</a>
                </form>
            </div>
        </div>
    @endif
@endsection
