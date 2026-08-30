@props(['active'])

@php
    $items = [
        'structures' => 'Salary Structures',
        'grades' => 'Salary Grades',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.compensation.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
