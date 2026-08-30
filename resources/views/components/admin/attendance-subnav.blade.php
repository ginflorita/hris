@props(['active'])

@php
    $items = [
        'attendances' => 'Attendance',
        'schedules' => 'Schedules',
        'shifts' => 'Shifts',
        'overtime' => 'Overtime',
        'holidays' => 'Holidays',
        'report' => 'Report',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.attendance.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
