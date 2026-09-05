{{--
    Employee-facing portal (blueprint §41 "Employee Portal Sidebar").
    Same "static placeholder until built" convention as the admin
    sidebar (layouts/partials/sidebar.blade.php) -- My Payslips (Phase
    12), My Profile/Employment/Documents (13a, read-only), My
    Leave/Leave Request/My Overtime (13b, self-service submit + cancel),
    My Attendance (13c, correction requests), Request COE (13d), Requests
    (13f, an aggregated view across all five request types), and Update
    My Information (20c, routed through the Workflow engine rather than
    a direct edit) are the real links so far; everything else
    (Performance/Training, Announcements, Notifications) waits on
    modules that don't exist yet.
--}}
<div class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <span class="offcanvas-title fw-semibold" id="appSidebarLabel">HRIS</span>
        <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-2">
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('portal.profile.*') ? 'active' : '' }}" href="{{ route('portal.profile.show') }}">
                    Dashboard
                </a>
            </li>
        </ul>

        @foreach ([
            'MY HR' => [
                'My Profile' => ['route' => 'portal.profile.show', 'active' => ['portal.profile.*']],
                'My Employment' => ['route' => 'portal.profile.show', 'active' => ['portal.profile.*']],
                'Update My Information' => ['route' => 'portal.information-change.index', 'active' => ['portal.information-change.*']],
            ],
            'ATTENDANCE' => [
                'My Attendance' => ['route' => 'portal.attendance.index', 'active' => ['portal.attendance.*']],
                'My Schedule' => null,
                'My Overtime' => ['route' => 'portal.overtime.index', 'active' => ['portal.overtime.*']],
            ],
            'LEAVE' => [
                'My Leave' => ['route' => 'portal.leave.index', 'active' => ['portal.leave.*']],
                'Leave Request' => ['route' => 'portal.leave.create'],
            ],
            'PAYROLL' => [
                'My Payslips' => ['route' => 'portal.payslips.index', 'active' => ['portal.payslips.*']],
                'My Compensation' => null,
                'My Benefits' => null,
            ],
            'DOCUMENTS' => [
                'My Documents' => ['route' => 'portal.profile.show', 'active' => ['portal.profile.*']],
                'Request COE' => ['route' => 'portal.coe.index', 'active' => ['portal.coe.*']],
            ],
            'OTHER' => [
                'Performance', 'Training',
                'Requests' => ['route' => 'portal.requests.index', 'active' => ['portal.requests.*']],
                'Announcements', 'Notifications',
            ],
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
