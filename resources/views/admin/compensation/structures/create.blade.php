@extends('layouts.admin')

@section('title', 'Add salary structure')

@php($breadcrumbs = [['label' => 'Compensation'], ['label' => 'Salary Structures', 'url' => route('admin.compensation.structures.index')], ['label' => 'Add structure']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.compensation.structures.store') }}">
                @csrf

                @include('admin.compensation.structures._form-fields', ['salaryStructure' => null])

                <button type="submit" class="btn btn-primary">Create structure</button>
                <a href="{{ route('admin.compensation.structures.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
