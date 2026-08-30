@extends('layouts.admin')

@section('title', 'Edit cost center')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Cost Centers', 'url' => route('admin.organization.cost-centers.index')], ['label' => $costCenter->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.organization.cost-centers.update', $costCenter) }}">
                @csrf
                @method('PUT')

                @include('admin.organization.cost-centers._form-fields')

                <button type="submit" class="btn btn-primary">Save cost center</button>
                <a href="{{ route('admin.organization.cost-centers.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
