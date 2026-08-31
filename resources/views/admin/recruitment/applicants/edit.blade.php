@extends('layouts.admin')

@section('title', 'Edit Applicant')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Applicants', 'url' => route('admin.recruitment.applicants.index')], ['label' => 'Edit']])

@section('content')
    <x-admin.recruitment-subnav active="applicants" />

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.recruitment.applicants.update', $applicant) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.recruitment.applicants._form-fields')

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.recruitment.applicants.show', $applicant) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
