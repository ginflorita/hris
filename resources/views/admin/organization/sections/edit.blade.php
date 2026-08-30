@extends('layouts.admin')

@section('title', 'Edit section')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Sections', 'url' => route('admin.organization.sections.index')], ['label' => $section->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.sections.update', $section) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.sections._form-fields')

                <button type="submit" class="btn btn-primary">Save section</button>
                <a href="{{ route('admin.organization.sections.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
