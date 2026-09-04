@extends('layouts.admin')

@section('title', 'Add workflow')

@php($breadcrumbs = [['label' => 'Workflows', 'url' => route('admin.workflow.definitions.index')], ['label' => 'Add workflow']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.workflow.definitions.store') }}">
                @csrf

                @include('admin.workflow.definitions._form-fields', ['workflowDefinition' => null])

                <button type="submit" class="btn btn-primary">Create workflow</button>
                <a href="{{ route('admin.workflow.definitions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
