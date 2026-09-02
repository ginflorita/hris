@extends('layouts.admin')

@section('title', 'Add training provider')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Providers', 'url' => route('admin.training.providers.index')], ['label' => 'Add provider']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.providers.store') }}">
                @csrf

                @include('admin.training.providers._form-fields', ['provider' => null])

                <button type="submit" class="btn btn-primary">Create provider</button>
                <a href="{{ route('admin.training.providers.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
