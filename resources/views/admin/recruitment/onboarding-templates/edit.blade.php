@extends('layouts.admin')

@section('title', 'Edit '.$onboardingTemplate->name)

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Onboarding Templates', 'url' => route('admin.recruitment.onboarding-templates.index')], ['label' => $onboardingTemplate->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.recruitment.onboarding-templates.update', $onboardingTemplate) }}">
                @csrf
                @method('PUT')

                @include('admin.recruitment.onboarding-templates._form-fields')

                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('admin.recruitment.onboarding-templates.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
