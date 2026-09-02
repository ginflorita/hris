@props(['active'])

@php
    $items = [
        'courses' => 'Courses',
        'providers' => 'Providers',
        'competencies' => 'Competencies',
        'skills' => 'Skills',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.training.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
