@extends('layouts.admin')

@section('title', 'Edit branch')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Branches', 'url' => route('admin.organization.branches.index')], ['label' => $branch->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.branches.update', $branch) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.branches._form-fields')

                <button type="submit" class="btn btn-primary">Save branch</button>
                <a href="{{ route('admin.organization.branches.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
