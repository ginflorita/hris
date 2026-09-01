<div class="modal fade" id="assignOnboardingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employees.onboardings.store', $employee) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Assign Onboarding</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Template</label>
                        <select name="onboarding_template_id" class="form-select" required>
                            <option value="">Select a template</option>
                            @foreach ($onboardingTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        @if ($onboardingTemplates->isEmpty())
                            <div class="form-text">No active onboarding templates for this company yet.</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
