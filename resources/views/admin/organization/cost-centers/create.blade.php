@extends('layouts.admin')

@section('title', 'Add cost center')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Cost Centers', 'url' => route('admin.organization.cost-centers.index')], ['label' => 'Add cost center']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.cost-centers.store') }}">
                @csrf

                @include('admin.organization.cost-centers._form-fields', ['costCenter' => null])

                <button type="submit" class="btn btn-primary">Create cost center</button>
                <a href="{{ route('admin.organization.cost-centers.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
