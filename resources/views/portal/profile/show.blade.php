@extends('layouts.portal')

@section('title', 'My Profile')

@php($breadcrumbs = [['label' => 'My Profile']])

@section('content')
    @unless ($employee)
        <div class="alert alert-warning">
            Your account isn't linked to an employee record yet. Contact HR if you believe this is a mistake.
        </div>
    @else
        <div class="mb-3">
            <h1 class="h4 mb-1">{{ $employee->full_name }}</h1>
            <div class="text-body-secondary">
                <code>{{ $employee->employee_number }}</code> &middot; {{ $employee->company->name }}
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist">
            @foreach (['overview' => 'Overview', 'employment' => 'Employment History', 'documents' => 'Documents'] as $tabId => $tabLabel)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $tabId }}-tab" data-bs-toggle="tab"
                            data-bs-target="#{{ $tabId }}" type="button" role="tab">{{ $tabLabel }}</button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                @php($current = $employee->employments->firstWhere('end_date', null))
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Personal</div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    @foreach ([
                                        'Full name' => $employee->full_name,
                                        'Preferred name' => $employee->preferred_name,
                                        'Birth date' => $employee->birth_date?->format('M d, Y'),
                                        'Gender' => $employee->gender ? ucwords(str_replace('_', ' ', $employee->gender->value)) : null,
                                        'Civil status' => $employee->civil_status ? ucfirst($employee->civil_status->value) : null,
                                        'Nationality' => $employee->nationality,
                                        'Email' => $employee->email,
                                        'Mobile' => $employee->mobile,
                                    ] as $label => $value)
                                        <dt class="col-sm-5 text-body-secondary fw-normal">{{ $label }}</dt>
                                        <dd class="col-sm-7">{{ $value ?? '—' }}</dd>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Current employment</div>
                            <div class="card-body">
                                @if ($current)
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5 text-body-secondary fw-normal">Position</dt>
                                        <dd class="col-sm-7">{{ $current->position?->title ?? '—' }}</dd>
                                        <dt class="col-sm-5 text-body-secondary fw-normal">Department</dt>
                                        <dd class="col-sm-7">{{ $current->department?->name ?? '—' }}</dd>
                                        <dt class="col-sm-5 text-body-secondary fw-normal">Branch</dt>
                                        <dd class="col-sm-7">{{ $current->branch?->name ?? '—' }}</dd>
                                        <dt class="col-sm-5 text-body-secondary fw-normal">Manager</dt>
                                        <dd class="col-sm-7">{{ $current->manager?->full_name ?? '—' }}</dd>
                                        <dt class="col-sm-5 text-body-secondary fw-normal">Employment type</dt>
                                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $current->employment_type->value)) }}</dd>
                                        <dt class="col-sm-5 text-body-secondary fw-normal">Since</dt>
                                        <dd class="col-sm-7">{{ $current->effective_date->format('M d, Y') }}</dd>
                                    </dl>
                                @else
                                    <p class="text-body-secondary mb-0">No employment record on file.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="employment" role="tabpanel">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Effective</th>
                                    <th>Change</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employee->employments as $record)
                                    <tr class="{{ $record->isCurrent() ? 'table-active' : '' }}">
                                        <td>
                                            {{ $record->effective_date->format('M d, Y') }}
                                            @if ($record->isCurrent())
                                                <span class="badge text-bg-success">Current</span>
                                            @endif
                                        </td>
                                        <td>{{ ucwords(str_replace('_', ' ', $record->change_type->value)) }}</td>
                                        <td>{{ $record->position?->title ?? '—' }}</td>
                                        <td>{{ $record->department?->name ?? '—' }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $record->employment_type->value)) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $record->status->value)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-body-secondary py-3">No employment history yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="documents" role="tabpanel">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>File</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employee->documents as $document)
                                    <tr>
                                        <td>{{ $document->title }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $document->document_type->value)) }}</td>
                                        <td>
                                            <a href="{{ route('portal.profile.documents.download', $document) }}">{{ $document->original_filename }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-body-secondary py-3">No documents on file.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endunless
@endsection
