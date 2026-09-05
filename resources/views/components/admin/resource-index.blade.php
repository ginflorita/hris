@props(['createUrl' => null, 'createModal' => null, 'createLabel' => null, 'statusKey' => 'status', 'errorKey' => null])

@session($statusKey)
    <div class="alert alert-success py-2">{{ $value }}</div>
@endsession

@if ($errorKey && $errors->has($errorKey))
    <div class="alert alert-danger py-2">{{ $errors->first($errorKey) }}</div>
@endif

@if ($createModal)
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $createModal }}">{{ $createLabel }}</button>
    </div>
@elseif ($createUrl)
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

{{--
    Add/Edit on this and every other modal-based CRUD page happens in a
    Bootstrap modal rendered on this same index page (one per row for
    edit, matching the per-employee sub-resource modals elsewhere in this
    app) rather than a separate create/edit page. A validation failure
    still redirects back here the normal Laravel way (nothing server-side
    needs to know which modal that was) — this is the one bit of glue
    that reopens the *right* modal so the user doesn't lose which record
    they were editing: every such form carries a hidden `_modal` input
    naming its own modal id, `old('_modal')` survives the redirect via
    Laravel's automatic input flash, and this script reopens that modal
    on load. Silently does nothing on a page with no such forms.
--}}
@if ($errors->any() && old('_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById(@json(old('_modal')));
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    </script>
@endif
