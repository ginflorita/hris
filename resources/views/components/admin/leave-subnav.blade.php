@props(['active'])

@php
    $items = [
        'requests' => 'Requests',
        'calendar' => 'Calendar',
        'types' => 'Leave Types',
        'policies' => 'Policies',
        'report' => 'Report',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.leave.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
