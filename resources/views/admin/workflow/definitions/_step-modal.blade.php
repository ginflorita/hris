@php($nextOrder = $workflowDefinition->steps->max('step_order') + 1)

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $step ? route('admin.workflow.definitions.steps.update', [$workflowDefinition, $step]) : route('admin.workflow.definitions.steps.store', $workflowDefinition) }}">
                @csrf
                @if ($step)
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $step ? 'Edit step' : 'Add step' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Order</label>
                        <input type="number" min="1" name="step_order" value="{{ $step?->step_order ?? $nextOrder }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ $step?->name }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approver</label>
                        <select name="approver_type" class="form-select workflow-approver-type" required>
                            @foreach (\App\Enums\WorkflowApproverType::cases() as $case)
                                <option value="{{ $case->value }}" {{ $step?->approver_type?->value === $case->value ? 'selected' : '' }}>
                                    {{ $case === \App\Enums\WorkflowApproverType::Manager ? "The requester's manager" : 'Anyone with a specific permission' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 workflow-permission-field" {{ $step?->approver_type === \App\Enums\WorkflowApproverType::Manager ? 'style=display:none' : '' }}>
                        <label class="form-label">Required permission</label>
                        <select name="required_permission" class="form-select">
                            <option value="">Select a permission</option>
                            @foreach ($groupedPermissions as $module => $permissions)
                                <optgroup label="{{ $module }}">
                                    @foreach ($permissions as $permission)
                                        <option value="{{ $permission->name }}" {{ $step?->required_permission === $permission->name ? 'selected' : '' }}>
                                            {{ $permission->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('change', function (event) {
            if (!event.target.classList.contains('workflow-approver-type')) {
                return;
            }
            var field = event.target.closest('form').querySelector('.workflow-permission-field');
            field.style.display = event.target.value === 'manager' ? 'none' : '';
        });
    </script>
@endonce
