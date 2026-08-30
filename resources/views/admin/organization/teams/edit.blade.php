@extends('layouts.admin')

@section('title', 'Edit team')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Teams', 'url' => route('admin.organization.teams.index')], ['label' => $team->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.teams.update', $team) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.teams._form-fields')

                <button type="submit" class="btn btn-primary">Save team</button>
                <a href="{{ route('admin.organization.teams.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
