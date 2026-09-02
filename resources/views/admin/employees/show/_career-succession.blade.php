<h6>Career Development Plans</h6>

@can('performance.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCareerPlanModal">Add Plan</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Target Position</th>
                    <th>Target Date</th>
                    <th>Development Actions</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->careerDevelopmentPlans as $careerPlan)
                    <tr>
                        <td>{{ $careerPlan->targetPosition?->title ?? '—' }}</td>
                        <td>{{ $careerPlan->target_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($careerPlan->development_actions, 60) }}</td>
                        <td>
                            @if ($careerPlan->status->value === 'achieved')
                                <span class="badge text-bg-success">Achieved</span>
                            @elseif ($careerPlan->status->value === 'cancelled')
                                <span class="badge text-bg-secondary">Cancelled</span>
                            @else
                                <span class="badge text-bg-primary">Active</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('performance.manage')
                                @if ($careerPlan->status->value === 'active')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCareerPlanModal{{ $careerPlan->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.employees.career-development-plans.achieve', [$employee, $careerPlan]) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-success">Achieved</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.employees.career-development-plans.cancel', [$employee, $careerPlan]) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No career development plans yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('performance.manage')
    <div class="modal fade" id="addCareerPlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.career-development-plans.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Career Development Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.employees.show._career-plan-fields', ['plan' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($employee->careerDevelopmentPlans as $careerPlan)
        @if ($careerPlan->status->value === 'active')
            <div class="modal fade" id="editCareerPlanModal{{ $careerPlan->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.employees.career-development-plans.update', [$employee, $careerPlan]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Career Development Plan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @include('admin.employees.show._career-plan-fields', ['plan' => $careerPlan])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endcan

<h6 class="mt-4">Succession Candidacies</h6>

@can('performance.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSuccessionCandidateModal">Add Candidacy</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Readiness</th>
                    <th>Notes</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->successionCandidacies as $succession)
                    <tr>
                        <td>{{ $succession->position->title }}</td>
                        <td>{{ $succession->readiness->label() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($succession->notes, 60) ?: '—' }}</td>
                        <td class="text-end">
                            @can('performance.manage')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSuccessionCandidateModal{{ $succession->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.succession-candidacies.destroy', [$employee, $succession]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this candidacy?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-body-secondary py-3">Not a succession candidate for any position yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('performance.manage')
    <div class="modal fade" id="addSuccessionCandidateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.succession-candidacies.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Succession Candidacy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.employees.show._succession-candidate-fields', ['candidate' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Candidacy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($employee->successionCandidacies as $succession)
        <div class="modal fade" id="editSuccessionCandidateModal{{ $succession->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.employees.succession-candidacies.update', [$employee, $succession]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Succession Candidacy</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('admin.employees.show._succession-candidate-fields', ['candidate' => $succession])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endcan
