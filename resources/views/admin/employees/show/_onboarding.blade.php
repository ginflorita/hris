@php($hasIncomplete = $employee->onboardings->contains(fn ($o) => ! $o->isComplete()))

@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        @unless ($hasIncomplete)
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignOnboardingModal">Assign Onboarding</button>
        @endunless
    </div>
@endcan

@forelse ($employee->onboardings as $onboarding)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $onboarding->template?->name ?? 'Onboarding checklist' }}</strong>
                <span class="text-body-secondary small">
                    &middot; assigned {{ $onboarding->created_at->format('M d, Y') }}
                    @if ($onboarding->assignedBy)
                        by {{ $onboarding->assignedBy->name }}
                    @endif
                </span>
            </div>
            @if ($onboarding->isComplete())
                <span class="badge text-bg-success">Complete</span>
            @else
                <span class="badge text-bg-warning">{{ $onboarding->progressPercentage() }}% complete</span>
            @endif
        </div>
        <div class="progress mx-3 mt-3" style="height: 6px;" role="progressbar" aria-valuenow="{{ $onboarding->progressPercentage() }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar {{ $onboarding->isComplete() ? 'bg-success' : '' }}" style="width: {{ $onboarding->progressPercentage() }}%"></div>
        </div>
        @if ($onboarding->notes)
            <div class="px-3 pt-3 text-body-secondary small">{{ $onboarding->notes }}</div>
        @endif
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th style="width: 2.5rem;"></th>
                        <th>Task</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($onboarding->tasks as $task)
                        <tr>
                            <td>
                                @can('employees.update')
                                    <form method="POST" action="{{ route('admin.employees.onboardings.tasks.update', [$employee, $onboarding, $task]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_completed" value="{{ $task->is_completed ? '0' : '1' }}">
                                        <input type="checkbox" class="form-check-input" {{ $task->is_completed ? 'checked' : '' }} onchange="this.form.submit()">
                                    </form>
                                @else
                                    <input type="checkbox" class="form-check-input" disabled {{ $task->is_completed ? 'checked' : '' }}>
                                @endcan
                            </td>
                            <td>
                                <span class="{{ $task->is_completed ? 'text-decoration-line-through text-body-secondary' : '' }}">{{ $task->title }}</span>
                                @if ($task->description)
                                    <div class="text-body-secondary small">{{ $task->description }}</div>
                                @endif
                            </td>
                            <td class="text-body-secondary small">
                                @if ($task->is_completed)
                                    {{ $task->completed_at->format('M d, Y') }}
                                    @if ($task->completedBy)
                                        &middot; {{ $task->completedBy->name }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-body-secondary py-3">This template had no tasks.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-body-secondary py-4">No onboarding checklist assigned yet.</div>
    </div>
@endforelse

@can('employees.update')
    @unless ($hasIncomplete)
        @include('admin.employees.show._onboarding-assign-modal')
    @endunless
@endcan
