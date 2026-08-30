@extends('layouts.admin')

@section('title', 'Add division')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Divisions', 'url' => route('admin.organization.divisions.index')], ['label' => 'Add division']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.divisions.store') }}">
                @csrf

                @include('admin.organization.divisions._form-fields', ['division' => null])

                <button type="submit" class="btn btn-primary">Create division</button>
                <a href="{{ route('admin.organization.divisions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
