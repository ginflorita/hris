@extends('layouts.admin')

@section('title', 'Edit workflow')

@php($breadcrumbs = [['label' => 'Workflows', 'url' => route('admin.workflow.definitions.index')], ['label' => $workflowDefinition->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.workflow.definitions.update', $workflowDefinition) }}">
                @csrf
                @method('PUT')

                @include('admin.workflow.definitions._form-fields')

                <button type="submit" class="btn btn-primary">Save workflow</button>
                <a href="{{ route('admin.workflow.definitions.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
