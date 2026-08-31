@extends('layouts.admin')

@section('title', 'Job Postings')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Postings']])

@section('content')
    <x-admin.recruitment-subnav active="postings" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="row g-2">
            <div class="col-auto">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\JobPostingStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                            {{ ucfirst($case->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
        @can('recruitment.manage')
            <a href="{{ route('admin.recruitment.postings.create') }}" class="btn btn-primary btn-sm">New Posting</a>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($postings as $posting)
                        <tr>
                            <td>{{ $posting->title }}</td>
                            <td>{{ $posting->company->name }}</td>
                            <td>{{ $posting->jobRequisition->department->name ?? '—' }}</td>
                            <td>{{ $posting->is_internal ? 'Internal' : 'External' }}</td>
                            <td>
                                @if ($posting->status->value === 'published')
                                    <span class="badge text-bg-success">Published</span>
                                @elseif ($posting->status->value === 'closed')
                                    <span class="badge text-bg-secondary">Closed</span>
                                @else
                                    <span class="badge text-bg-warning">Draft</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('recruitment.manage')
                                    <a href="{{ route('admin.recruitment.postings.edit', $posting) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @if ($posting->status->value === 'draft')
                                        <form method="POST" action="{{ route('admin.recruitment.postings.publish', $posting) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Publish</button>
                                        </form>
                                    @elseif ($posting->status->value === 'published')
                                        <form method="POST" action="{{ route('admin.recruitment.postings.close', $posting) }}" class="d-inline"
                                              onsubmit="return confirm('Close this posting?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Close</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-3">No job postings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $postings->links() }}</div>
@endsection
