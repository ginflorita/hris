@extends('layouts.admin')

@section('title', 'Applicants')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Applicants']])

@section('content')
    <x-admin.recruitment-subnav active="applicants" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="row g-2 flex-grow-1">
            <div class="col-auto flex-grow-1" style="max-width: 320px;">
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search name or email">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary">Search</button>
            </div>
        </form>
        @can('recruitment.manage')
            <a href="{{ route('admin.recruitment.applicants.create') }}" class="btn btn-primary btn-sm">Add Applicant</a>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Source</th>
                        <th>Applications</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applicants as $applicant)
                        <tr>
                            <td>{{ $applicant->full_name }}</td>
                            <td>{{ $applicant->email }}</td>
                            <td>{{ $applicant->source->label() }}</td>
                            <td>{{ $applicant->applications_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.recruitment.applicants.show', $applicant) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-3">No applicants yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $applicants->links() }}</div>
@endsection
