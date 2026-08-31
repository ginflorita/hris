@extends('layouts.admin')

@section('title', 'Add Applicant')

@php($breadcrumbs = [['label' => 'Recruitment'], ['label' => 'Applicants', 'url' => route('admin.recruitment.applicants.index')], ['label' => 'Add']])

@section('content')
    <x-admin.recruitment-subnav active="applicants" />

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.recruitment.applicants.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.recruitment.applicants._form-fields')

                <button type="submit" class="btn btn-primary">Add Applicant</button>
                <a href="{{ route('admin.recruitment.applicants.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
