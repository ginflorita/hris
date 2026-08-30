@extends('layouts.admin')

@section('title', 'Add location')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Locations', 'url' => route('admin.organization.locations.index')], ['label' => 'Add location']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.locations.store') }}">
                @csrf

                @include('admin.organization.locations._form-fields', ['location' => null])

                <button type="submit" class="btn btn-primary">Create location</button>
                <a href="{{ route('admin.organization.locations.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
