@extends('layouts.admin')

@section('title', 'Add branch')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Branches', 'url' => route('admin.organization.branches.index')], ['label' => 'Add branch']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.branches.store') }}">
                @csrf

                @include('admin.organization.branches._form-fields', ['branch' => null])

                <button type="submit" class="btn btn-primary">Create branch</button>
                <a href="{{ route('admin.organization.branches.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
