@can('performance.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGoalModal">Add Goal</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Goal</th>
                    <th>Cycle</th>
                    <th>Target Date</th>
                    <th>Progress</th>
                    <th>Weight</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->performanceGoals as $goal)
                    <tr>
                        <td>
                            {{ $goal->title }}
                            @if ($goal->description)
                                <div class="text-body-secondary small">{{ $goal->description }}</div>
                            @endif
                        </td>
                        <td>{{ $goal->performanceCycle->name }}</td>
                        <td>{{ $goal->target_date?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @if ($goal->target_value !== null)
                                {{ $goal->actual_value ?? '—' }} / {{ $goal->target_value }} {{ $goal->unit }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $goal->weight ? $goal->weight.'%' : '—' }}</td>
                        <td>
                            @if ($goal->status->value === 'completed')
                                <span class="badge text-bg-success">Completed</span>
                            @elseif ($goal->status->value === 'cancelled')
                                <span class="badge text-bg-secondary">Cancelled</span>
                            @elseif ($goal->status->value === 'in_progress')
                                <span class="badge text-bg-primary">In Progress</span>
                            @else
                                <span class="badge text-bg-warning">Not Started</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('performance.manage')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editGoalModal{{ $goal->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.performance-goals.destroy', [$employee, $goal]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this goal?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-body-secondary py-3">No goals set yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('performance.manage')
    <div class="modal fade" id="addGoalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.performance-goals.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Goal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.employees.show._performance-goal-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($employee->performanceGoals as $goal)
        <div class="modal fade" id="editGoalModal{{ $goal->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.employees.performance-goals.update', [$employee, $goal]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Goal</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('admin.employees.show._performance-goal-fields', ['goal' => $goal])
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

<h6 class="mt-4">Performance Reviews</h6>

@can('performance.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addReviewModal">Add Review</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Cycle</th>
                    <th>Reviewer</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->performanceReviews as $review)
                    <tr>
                        <td>{{ ucfirst($review->type->value) }}</td>
                        <td>{{ $review->performanceCycle->name }}</td>
                        <td>{{ $review->reviewer->full_name }}</td>
                        <td>{{ $review->rating ?? '—' }} / 5</td>
                        <td>
                            @if ($review->status->value === 'acknowledged')
                                <span class="badge text-bg-success">Acknowledged</span>
                            @elseif ($review->status->value === 'submitted')
                                <span class="badge text-bg-primary">Submitted</span>
                            @else
                                <span class="badge text-bg-secondary">Draft</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('performance.manage')
                                @if ($review->status->value === 'draft')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editReviewModal{{ $review->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.employees.performance-reviews.submit', [$employee, $review]) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Submit</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.employees.performance-reviews.destroy', [$employee, $review]) }}" class="d-inline"
                                          onsubmit="return confirm('Remove this review?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                @elseif ($review->status->value === 'submitted')
                                    <form method="POST" action="{{ route('admin.employees.performance-reviews.acknowledge', [$employee, $review]) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-success">Acknowledge</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-body-secondary py-3">No reviews yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('performance.manage')
    <div class="modal fade" id="addReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.performance-reviews.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.employees.show._performance-review-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($employee->performanceReviews as $review)
        @if ($review->status->value === 'draft')
            <div class="modal fade" id="editReviewModal{{ $review->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.employees.performance-reviews.update', [$employee, $review]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Review</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @include('admin.employees.show._performance-review-fields', ['review' => $review])
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
