@extends('layouts.admin')

@section('title', 'Edit position')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Positions', 'url' => route('admin.organization.positions.index')], ['label' => $position->title]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.positions.update', $position) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.positions._form-fields')

                <button type="submit" class="btn btn-primary">Save position</button>
                <a href="{{ route('admin.organization.positions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
