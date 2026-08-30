@extends('layouts.admin')

@section('title', 'Add role')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Roles', 'url' => route('admin.roles.index')], ['label' => 'Add role']])

@section('content')
    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf

                @include('admin.roles._form-fields', ['role' => null])

                <button type="submit" class="btn btn-primary">Create role</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
