@extends('layouts.admin')

@section('title', 'Add section')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Sections', 'url' => route('admin.organization.sections.index')], ['label' => 'Add section']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.sections.store') }}">
                @csrf

                @include('admin.organization.sections._form-fields', ['section' => null])

                <button type="submit" class="btn btn-primary">Create section</button>
                <a href="{{ route('admin.organization.sections.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
