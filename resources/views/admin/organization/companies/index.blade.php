@extends('layouts.admin')

@section('title', 'Companies')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Companies']])

@section('content')
    <x-admin.org-subnav active="companies" />

    <x-admin.resource-index
        :create-modal="auth()->user()->can('organization.manage') ? 'createCompanyModal' : null"
        create-label="Add company"
        error-key="company"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Branches</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($companies as $company)
                <tr>
                    <td>
                        @can('organization.manage')
                            <a href="#" data-bs-toggle="modal" data-bs-target="#editCompanyModal{{ $company->id }}">{{ $company->name }}</a>
                        @else
                            {{ $company->name }}
                        @endcan
                    </td>
                    <td><code>{{ $company->code }}</code></td>
                    <td>{{ $company->branches()->count() }}</td>
                    <td>
                        @if ($company->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCompanyModal{{ $company->id }}">Edit</button>
                            <form method="POST" action="{{ route('admin.organization.companies.destroy', $company) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $company->name }}? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-body-secondary py-3">No companies yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $companies->links() }}</div>

    @can('organization.manage')
        <x-admin.form-modal id="createCompanyModal" title="Add company" :action="route('admin.organization.companies.store')" submit-label="Create company">
            @include('admin.organization.companies._form-fields', ['company' => null])
        </x-admin.form-modal>

        @foreach ($companies as $company)
            <x-admin.form-modal :id="'editCompanyModal'.$company->id" title="Edit {{ $company->name }}"
                :action="route('admin.organization.companies.update', $company)" method="PUT" submit-label="Save company">
                @include('admin.organization.companies._form-fields')
            </x-admin.form-modal>
        @endforeach
    @endcan
@endsection
