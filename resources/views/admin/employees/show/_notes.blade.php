@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal">Add note</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Note</th>
                    <th>Confidential</th>
                    <th>By</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->notes as $note)
                    <tr>
                        <td>{{ $note->note }}</td>
                        <td>{{ $note->is_confidential ? 'Yes' : '' }}</td>
                        <td>{{ $note->createdBy?->name ?? '—' }}</td>
                        <td>{{ $note->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            @can('employees.update')
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editNoteModal{{ $note->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.employees.notes.destroy', [$employee, $note]) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this note?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-3">No notes on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._note-modal', ['note' => null, 'modalId' => 'addNoteModal'])
    @foreach ($employee->notes as $note)
        @include('admin.employees.show._note-modal', ['note' => $note, 'modalId' => 'editNoteModal'.$note->id])
    @endforeach
@endcan
