@extends('layouts.admin')

@section('title', 'Edit competency')

@php($breadcrumbs = [['label' => 'Training'], ['label' => 'Competencies', 'url' => route('admin.training.competencies.index')], ['label' => $competency->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.training.competencies.update', $competency) }}">
                @csrf
                @method('PUT')

                @include('admin.training.competencies._form-fields')

                <button type="submit" class="btn btn-primary">Save competency</button>
                <a href="{{ route('admin.training.competencies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
