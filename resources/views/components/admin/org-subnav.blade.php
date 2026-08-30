@props(['active'])

@php
    $items = [
        'companies' => 'Companies',
        'branches' => 'Branches',
        'locations' => 'Locations',
        'divisions' => 'Divisions',
        'departments' => 'Departments',
        'sections' => 'Sections',
        'teams' => 'Teams',
        'positions' => 'Positions',
        'job-levels' => 'Job Levels',
        'job-grades' => 'Job Grades',
        'cost-centers' => 'Cost Centers',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.organization.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
