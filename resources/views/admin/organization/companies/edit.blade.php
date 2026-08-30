@extends('layouts.admin')

@section('title', 'Edit company')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Companies', 'url' => route('admin.organization.companies.index')], ['label' => $company->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.companies.update', $company) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.companies._form-fields')

                <button type="submit" class="btn btn-primary">Save company</button>
                <a href="{{ route('admin.organization.companies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
