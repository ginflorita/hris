@extends('layouts.admin')

@section('title', 'Add position')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Positions', 'url' => route('admin.organization.positions.index')], ['label' => 'Add position']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.positions.store') }}">
                @csrf

                @include('admin.organization.positions._form-fields', ['position' => null])

                <button type="submit" class="btn btn-primary">Create position</button>
                <a href="{{ route('admin.organization.positions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
