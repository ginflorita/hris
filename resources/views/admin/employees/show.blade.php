@extends('layouts.admin')

@section('title', $employee->full_name)

@php($breadcrumbs = [['label' => 'Employees', 'url' => route('admin.employees.index')], ['label' => $employee->full_name]])

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">
                {{ $employee->full_name }}
                @if ($employee->isArchived())
                    <span class="badge text-bg-secondary align-middle">Archived</span>
                @endif
            </h1>
            <div class="text-body-secondary">
                <code>{{ $employee->employee_number }}</code> · {{ $employee->company->name }}
            </div>
        </div>
        <div>
            @can('employees.update')
                <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            @endcan
            @can('employees.archive')
                @if ($employee->isArchived())
                    <form method="POST" action="{{ route('admin.employees.restore', $employee) }}" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-outline-success btn-sm">Restore</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.employees.archive', $employee) }}" class="d-inline"
                          onsubmit="return confirm('Archive {{ $employee->full_name }}?');">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Archive</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach ([
            'overview' => 'Overview',
            'employment' => 'Employment History',
            'leave' => 'Leave',
            'compensation' => 'Compensation',
            'addresses' => 'Addresses',
            'contacts' => 'Contacts',
            'emergency-contacts' => 'Emergency Contacts',
            'government-ids' => 'Government IDs',
            'dependents' => 'Dependents',
            'documents' => 'Documents',
            'notes' => 'Notes',
            'onboarding' => 'Onboarding',
            'performance' => 'Performance',
            'skills-competencies' => 'Skills & Competencies',
            'training' => 'Training',
            'career-succession' => 'Career & Succession',
        ] as $tabId => $tabLabel)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $tabId }}-tab" data-bs-toggle="tab"
                        data-bs-target="#{{ $tabId }}" type="button" role="tab">{{ $tabLabel }}</button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            @include('admin.employees.show._overview')
        </div>
        <div class="tab-pane fade" id="employment" role="tabpanel">
            @include('admin.employees.show._employment')
        </div>
        <div class="tab-pane fade" id="leave" role="tabpanel">
            @include('admin.employees.show._leave')
        </div>
        <div class="tab-pane fade" id="compensation" role="tabpanel">
            @include('admin.employees.show._compensation')
        </div>
        <div class="tab-pane fade" id="addresses" role="tabpanel">
            @include('admin.employees.show._addresses')
        </div>
        <div class="tab-pane fade" id="contacts" role="tabpanel">
            @include('admin.employees.show._contacts')
        </div>
        <div class="tab-pane fade" id="emergency-contacts" role="tabpanel">
            @include('admin.employees.show._emergency-contacts')
        </div>
        <div class="tab-pane fade" id="government-ids" role="tabpanel">
            @include('admin.employees.show._government-ids')
        </div>
        <div class="tab-pane fade" id="dependents" role="tabpanel">
            @include('admin.employees.show._dependents')
        </div>
        <div class="tab-pane fade" id="documents" role="tabpanel">
            @include('admin.employees.show._documents')
        </div>
        <div class="tab-pane fade" id="notes" role="tabpanel">
            @include('admin.employees.show._notes')
        </div>
        <div class="tab-pane fade" id="onboarding" role="tabpanel">
            @include('admin.employees.show._onboarding')
        </div>
        <div class="tab-pane fade" id="performance" role="tabpanel">
            @include('admin.employees.show._performance')
        </div>
        <div class="tab-pane fade" id="skills-competencies" role="tabpanel">
            @include('admin.employees.show._skills-competencies')
        </div>
        <div class="tab-pane fade" id="training" role="tabpanel">
            @include('admin.employees.show._training')
        </div>
        <div class="tab-pane fade" id="career-succession" role="tabpanel">
            @include('admin.employees.show._career-succession')
        </div>
    </div>
@endsection
