@extends('layouts.admin')

@section('title', 'Edit Job Posting')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Postings', 'url' => route('admin.recruitment.postings.index')], ['label' => 'Edit']])

@section('content')
    <x-admin.recruitment-subnav active="postings" />

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <p class="text-body-secondary mb-3">
                Requisition: {{ $posting->jobRequisition->position->title ?? $posting->jobRequisition->department->name ?? '#'.$posting->jobRequisition->id }}
            </p>

            <form method="POST" action="{{ route('admin.recruitment.postings.update', $posting) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label" for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $posting->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description', $posting->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="is_internal" name="is_internal" value="1" class="form-check-input" {{ old('is_internal', $posting->is_internal) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_internal">Internal posting only</label>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="closes_at">Closes At</label>
                        <input type="date" id="closes_at" name="closes_at" class="form-control @error('closes_at') is-invalid @enderror" value="{{ old('closes_at', $posting->closes_at?->format('Y-m-d')) }}">
                        @error('closes_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.recruitment.postings.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
