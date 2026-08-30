@extends('layouts.admin')

@section('title', 'Add company')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Companies', 'url' => route('admin.organization.companies.index')], ['label' => 'Add company']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.companies.store') }}">
                @csrf

                @include('admin.organization.companies._form-fields', ['company' => null])

                <button type="submit" class="btn btn-primary">Create company</button>
                <a href="{{ route('admin.organization.companies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
