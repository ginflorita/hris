@extends('layouts.admin')

@section('title', 'Edit training provider')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Providers', 'url' => route('admin.training.providers.index')], ['label' => $provider->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.providers.update', $provider) }}">
                @csrf
                @method('PUT')

                @include('admin.training.providers._form-fields')

                <button type="submit" class="btn btn-primary">Save provider</button>
                <a href="{{ route('admin.training.providers.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
