@extends('layouts.admin')

@section('title', 'Edit role')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Roles', 'url' => route('admin.roles.index')], ['label' => $role->name]])

@section('content')
    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf
                @method('PUT')

                @include('admin.roles._form-fields')

                <button type="submit" class="btn btn-primary">Save role</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
