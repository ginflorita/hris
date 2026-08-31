{{--
    Employee-facing portal (blueprint §41 "Employee Portal Sidebar").
    Same "static placeholder until built" convention as the admin
    sidebar (layouts/partials/sidebar.blade.php) -- My Payslips is the
    only real link so far (Phase 12's "digital payslip portal" bullet);
    everything else here is Phase 13's job.
--}}
<div class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <span class="offcanvas-title fw-semibold" id="appSidebarLabel">HRIS</span>
        <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-2">
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('portal.payslips.*') ? 'active' : '' }}" href="{{ route('portal.payslips.index') }}">
                    Dashboard
                </a>
            </li>
        </ul>

        @foreach ([
            'MY HR' => [
                'My Profile' => null,
                'My Employment' => null,
            ],
            'ATTENDANCE' => [
                'My Attendance' => null,
                'My Schedule' => null,
                'My Overtime' => null,
            ],
            'LEAVE' => [
                'My Leave' => null,
                'Leave Request' => null,
            ],
            'PAYROLL' => [
                'My Payslips' => ['route' => 'portal.payslips.index', 'active' => ['portal.payslips.*']],
                'My Compensation' => null,
                'My Benefits' => null,
            ],
            'DOCUMENTS' => [
                'My Documents' => null,
                'Request COE' => null,
            ],
            'OTHER' => ['Performance', 'Training', 'Requests', 'Announcements', 'Notifications'],
            'ACCOUNT' => [
                'Security' => ['route' => 'security.index'],
                'Sessions' => ['route' => 'security.index'],
            ],
        ] as $section => $items)
            <div class="nav-section-title">{{ $section }}</div>
            <ul class="nav nav-pills flex-column mb-2">
                @foreach ($items as $label => $item)
                    @php($config = is_array($item) ? $item : null)
                    @php($label = is_int($label) ? $item : $label)
                    <li class="nav-item">
                        @if ($config && Route::has($config['route']))
                            <a class="nav-link {{ request()->routeIs(...($config['active'] ?? [$config['route']])) ? 'active' : '' }}"
                               href="{{ route($config['route']) }}">{{ $label }}</a>
                        @else
                            <span class="nav-link disabled text-body-secondary" aria-disabled="true">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>
