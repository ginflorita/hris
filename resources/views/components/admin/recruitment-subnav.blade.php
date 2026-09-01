@props(['active'])

@php
    $items = [
        'requisitions' => 'Requisitions',
        'postings' => 'Postings',
        'applicants' => 'Applicants',
        'applications' => 'Applications',
        'onboarding-templates' => 'Onboarding Templates',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.recruitment.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
