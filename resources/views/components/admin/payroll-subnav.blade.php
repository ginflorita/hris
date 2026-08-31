@props(['active'])

@php
    $items = [
        'contribution-rate-tables' => 'Contribution Rates',
        'tax-tables' => 'Tax Tables',
    ];
@endphp

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($items as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}"
               href="{{ route("admin.payroll.{$key}.index") }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
