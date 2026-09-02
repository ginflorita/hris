{{-- Read-only: enrollment decisions (status, certificates) are made from
     the session's own roster page, not here -- see
     Admin\TrainingEnrollmentController and Talent Management in
     CLAUDE.md for why this tab doesn't duplicate that surface. --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Dates</th>
                    <th>Status</th>
                    <th>Certificate</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->trainingEnrollments as $enrollment)
                    <tr>
                        <td>
                            @can('training.view')
                                <a href="{{ route('admin.training.courses.sessions.show', [$enrollment->session->course, $enrollment->session]) }}">{{ $enrollment->session->course->name }}</a>
                            @else
                                {{ $enrollment->session->course->name }}
                            @endcan
                        </td>
                        <td>{{ $enrollment->session->start_date->format('M d, Y') }} &ndash; {{ $enrollment->session->end_date->format('M d, Y') }}</td>
                        <td>
                            @if ($enrollment->status->value === 'completed')
                                <span class="badge text-bg-success">Completed</span>
                            @elseif ($enrollment->status->value === 'cancelled')
                                <span class="badge text-bg-secondary">Cancelled</span>
                            @elseif ($enrollment->status->value === 'no_show')
                                <span class="badge text-bg-warning">No Show</span>
                            @else
                                <span class="badge text-bg-primary">Enrolled</span>
                            @endif
                        </td>
                        <td>
                            @if ($enrollment->certificate_number)
                                {{ $enrollment->certificate_number }}
                                @if ($enrollment->certificate_expires_at)
                                    <div class="text-body-secondary small">Expires {{ $enrollment->certificate_expires_at->format('M d, Y') }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-body-secondary py-3">No training enrollments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
