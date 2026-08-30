@extends('layouts.admin')

@section('title', 'Add team')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Teams', 'url' => route('admin.organization.teams.index')], ['label' => 'Add team']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.teams.store') }}">
                @csrf

                @include('admin.organization.teams._form-fields', ['team' => null])

                <button type="submit" class="btn btn-primary">Create team</button>
                <a href="{{ route('admin.organization.teams.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
