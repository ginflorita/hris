@extends('layouts.admin')

@section('title', 'Add leave policy')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Policies', 'url' => route('admin.leave.policies.index')], ['label' => 'Add policy']])

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leave.policies.store') }}">
                @csrf

                @include('admin.leave.policies._form-fields', ['leavePolicy' => null])

                <button type="submit" class="btn btn-primary">Create policy</button>
                <a href="{{ route('admin.leave.policies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
