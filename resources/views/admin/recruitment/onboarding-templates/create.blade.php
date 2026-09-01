@extends('layouts.admin')

@section('title', 'Add onboarding template')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Onboarding Templates', 'url' => route('admin.recruitment.onboarding-templates.index')], ['label' => 'Add template']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.recruitment.onboarding-templates.store') }}">
                @csrf

                @include('admin.recruitment.onboarding-templates._form-fields', ['onboardingTemplate' => null])

                <button type="submit" class="btn btn-primary">Create template</button>
                <a href="{{ route('admin.recruitment.onboarding-templates.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
