@extends('layouts.admin')

@section('title', 'Edit skill')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Skills', 'url' => route('admin.training.skills.index')], ['label' => $skill->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.skills.update', $skill) }}">
                @csrf
                @method('PUT')

                @include('admin.training.skills._form-fields')

                <button type="submit" class="btn btn-primary">Save skill</button>
                <a href="{{ route('admin.training.skills.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
