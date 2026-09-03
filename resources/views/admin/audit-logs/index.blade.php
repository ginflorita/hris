@extends('layouts.admin')

@section('title', 'Audit Logs')

@php($breadcrumbs = [['label' => 'Audit Logs']])

@section('content')
    <form method="GET" class="row row-cols-auto g-2 mb-3">
        <div class="col">
            <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All modules</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" @selected($selectedModule === $module)>{{ $module }}</option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All actions</option>
                @foreach (\App\Enums\AuditAction::cases() as $case)
                    <option value="{{ $case->value }}" @selected($selectedAction === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <x-admin.resource-index>
        <thead>
            <tr>
                <th>When</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record</th>
                <th>Changes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="text-nowrap" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td><span class="badge text-bg-secondary">{{ $log->action->label() }}</span></td>
                    <td>{{ $log->module }}</td>
                    <td>
                        @if ($log->auditable_type)
                            <span class="text-body-secondary">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                        @else
                            <span class="text-body-secondary">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($log->before || $log->after)
                            <ul class="mb-0 small">
                                @foreach (array_unique([...array_keys($log->before ?? []), ...array_keys($log->after ?? [])]) as $field)
                                    <li>
                                        {{ $field }}:
                                        {{ $log->before[$field] ?? '—' }}
                                        &rarr;
                                        {{ $log->after[$field] ?? '—' }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-body-secondary">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No audit log entries yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
