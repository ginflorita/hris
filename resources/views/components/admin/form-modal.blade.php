{{--
    Shared shell for the add/edit-in-a-modal pattern used across this
    app's top-level CRUD pages (see the comment at the bottom of
    resource-index.blade.php for how a validation failure reopens the
    right one). Each module still owns its own field markup — pass it as
    the slot, typically an existing `_form-fields.blade.php` partial
    shared between what used to be separate create/edit pages.
--}}
@props(['id', 'title', 'action', 'method' => 'POST', 'submitLabel' => 'Save', 'enctype' => null])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ $action }}" @if ($enctype) enctype="{{ $enctype }}" @endif>
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                <input type="hidden" name="_modal" value="{{ $id }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ $slot }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
