@extends('layouts.admin')

@section('title', 'Add benefit plan')

@php($breadcrumbs = [['label' => 'Benefits'], ['label' => 'Plans', 'url' => route('admin.benefits.plans.index')], ['label' => 'Add plan']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.benefits.plans.store') }}">
                @csrf

                @include('admin.benefits.plans._form-fields', ['plan' => null])

                <button type="submit" class="btn btn-primary">Create plan</button>
                <a href="{{ route('admin.benefits.plans.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
