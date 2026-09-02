@extends('layouts.admin')

@section('title', 'Edit benefit plan')

@php($breadcrumbs = [['label' => 'Benefits'], ['label' => 'Plans', 'url' => route('admin.benefits.plans.index')], ['label' => $plan->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.benefits.plans.update', $plan) }}">
                @csrf
                @method('PUT')

                @include('admin.benefits.plans._form-fields')

                <button type="submit" class="btn btn-primary">Save plan</button>
                <a href="{{ route('admin.benefits.plans.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
