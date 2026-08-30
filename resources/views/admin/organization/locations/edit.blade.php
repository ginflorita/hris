@extends('layouts.admin')

@section('title', 'Edit location')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Locations', 'url' => route('admin.organization.locations.index')], ['label' => $location->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.locations.update', $location) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.locations._form-fields')

                <button type="submit" class="btn btn-primary">Save location</button>
                <a href="{{ route('admin.organization.locations.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
