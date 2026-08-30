@extends('layouts.admin')

@section('title', 'Edit leave policy')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Policies', 'url' => route('admin.leave.policies.index')], ['label' => $leavePolicy->name]])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leave.policies.update', $leavePolicy) }}">
                @csrf
                @method('PUT')

                @include('admin.leave.policies._form-fields')

                <button type="submit" class="btn btn-primary">Save policy</button>
                <a href="{{ route('admin.leave.policies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
