@extends('layouts.admin')

@section('title', 'Add skill')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Skills', 'url' => route('admin.training.skills.index')], ['label' => 'Add skill']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.skills.store') }}">
                @csrf

                @include('admin.training.skills._form-fields', ['skill' => null])

                <button type="submit" class="btn btn-primary">Create skill</button>
                <a href="{{ route('admin.training.skills.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
