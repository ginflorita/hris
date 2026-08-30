@props(['createUrl' => null, 'createLabel' => null, 'statusKey' => 'status', 'errorKey' => null])

@session($statusKey)
    <div class="alert alert-success py-2">{{ $value }}</div>
@endsession

@if ($errorKey && $errors->has($errorKey))
    <div class="alert alert-danger py-2">{{ $errors->first($errorKey) }}</div>
@endif

@if ($createUrl)
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ $createUrl }}" class="btn btn-primary btn-sm">{{ $createLabel }}</a>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            {{ $slot }}
        </table>
    </div>
</div>
