@extends('layouts.admin')

@section('title', 'Edit salary structure')

@php($breadcrumbs = [['label' => 'Compensation'], ['label' => 'Salary Structures', 'url' => route('admin.compensation.structures.index')], ['label' => $salaryStructure->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.compensation.structures.update', $salaryStructure) }}">
                @csrf
                @method('PUT')

                @include('admin.compensation.structures._form-fields')

                <button type="submit" class="btn btn-primary">Save structure</button>
                <a href="{{ route('admin.compensation.structures.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
