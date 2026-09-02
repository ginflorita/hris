<h6>Competencies</h6>

@can('training.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCompetencyModal">Add Competency</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Competency</th>
                    <th>Level</th>
                    <th>Assessed On</th>
                    <th>Assessed By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->employeeCompetencies as $competencyRating)
                    <tr>
                        <td>{{ $competencyRating->competency->name }}</td>
                        <td>{{ ucfirst($competencyRating->proficiency_level->value) }}</td>
                        <td>{{ $competencyRating->assessed_at?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $competencyRating->assessedBy?->full_name ?? '—' }}</td>
                        <td class="text-end">
                            @can('training.manage')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCompetencyModal{{ $competencyRating->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.competencies.destroy', [$employee, $competencyRating]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this rating?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No competencies rated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('training.manage')
    <div class="modal fade" id="addCompetencyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.competencies.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Competency Rating</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.employees.show._capability-rating-fields', ['kind' => 'competency', 'catalog' => $companyCompetencies, 'rating' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Rating</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($employee->employeeCompetencies as $competencyRating)
        <div class="modal fade" id="editCompetencyModal{{ $competencyRating->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.employees.competencies.update', [$employee, $competencyRating]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Competency Rating</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('admin.employees.show._capability-rating-fields', ['kind' => 'competency', 'catalog' => $companyCompetencies, 'rating' => $competencyRating])
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

<h6 class="mt-4">Skills</h6>

@can('training.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSkillModal">Add Skill</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Skill</th>
                    <th>Level</th>
                    <th>Assessed On</th>
                    <th>Assessed By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->employeeSkills as $skillRating)
                    <tr>
                        <td>{{ $skillRating->skill->name }}</td>
                        <td>{{ ucfirst($skillRating->proficiency_level->value) }}</td>
                        <td>{{ $skillRating->assessed_at?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $skillRating->assessedBy?->full_name ?? '—' }}</td>
                        <td class="text-end">
                            @can('training.manage')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSkillModal{{ $skillRating->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.skills.destroy', [$employee, $skillRating]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this rating?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No skills rated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('training.manage')
    <div class="modal fade" id="addSkillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.skills.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Skill Rating</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.employees.show._capability-rating-fields', ['kind' => 'skill', 'catalog' => $companySkills, 'rating' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Rating</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($employee->employeeSkills as $skillRating)
        <div class="modal fade" id="editSkillModal{{ $skillRating->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.employees.skills.update', [$employee, $skillRating]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Skill Rating</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('admin.employees.show._capability-rating-fields', ['kind' => 'skill', 'catalog' => $companySkills, 'rating' => $skillRating])
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
