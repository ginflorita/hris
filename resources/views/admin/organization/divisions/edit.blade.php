@extends('layouts.admin')

@section('title', 'Edit division')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Divisions', 'url' => route('admin.organization.divisions.index')], ['label' => $division->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.divisions.update', $division) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.divisions._form-fields')

                <button type="submit" class="btn btn-primary">Save division</button>
                <a href="{{ route('admin.organization.divisions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
