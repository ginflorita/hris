@extends('layouts.admin')

@section('title', 'Add competency')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Competencies', 'url' => route('admin.training.competencies.index')], ['label' => 'Add competency']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.competencies.store') }}">
                @csrf

                @include('admin.training.competencies._form-fields', ['competency' => null])

                <button type="submit" class="btn btn-primary">Create competency</button>
                <a href="{{ route('admin.training.competencies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
