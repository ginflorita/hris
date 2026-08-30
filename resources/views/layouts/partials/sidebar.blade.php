{{--
    Mostly still static placeholders — most modules aren't built yet (see
    CLAUDE.md "Status"). ADMINISTRATION > Users/Roles is real and
    permission-gated; the rest light up the same way as their phase lands.
--}}
<div class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <span class="offcanvas-title fw-semibold" id="appSidebarLabel">HRIS</span>
        <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-2">
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    Dashboard
                </a>
            </li>
        </ul>

        @foreach ([
            'WORKFORCE' => ['Employees', 'Organization', 'Positions', 'Employment', 'Documents'],
            'TIME & ATTENDANCE' => ['Attendance', 'Schedules', 'Shifts', 'Overtime', 'Holidays', 'Leave'],
            'PAYROLL' => ['Payroll', 'Payroll Periods', 'Compensation', 'Benefits', 'Payslips'],
            'TALENT' => ['Recruitment', 'Applicants', 'Onboarding', 'Performance', 'Training', 'Skills', 'Career'],
            'REPORTS' => ['HR Reports', 'Attendance Reports', 'Leave Reports', 'Payroll Reports', 'Analytics'],
            'ADMINISTRATION' => [
                'Users' => ['route' => 'admin.users.index', 'can' => ['viewAny', \App\Models\User::class]],
                'Roles' => ['route' => 'admin.roles.index', 'can' => ['viewAny', \App\Models\Role::class]],
                'Permissions' => ['route' => 'admin.permissions.index', 'can' => ['viewAny', \App\Models\Role::class]],
                'Workflows' => null,
                'Notifications' => null,
                'Announcements' => null,
                'Audit Logs' => null,
                'Settings' => null,
            ],
        ] as $section => $items)
            <div class="nav-section-title">{{ $section }}</div>
            <ul class="nav nav-pills flex-column mb-2">
                @foreach ($items as $label => $item)
                    @php($config = is_array($item) ? $item : null)
                    @php($label = is_int($label) ? $item : $label)
                    <li class="nav-item">
                        @if ($config && Route::has($config['route']) && auth()->user()?->can(...$config['can']))
                            <a class="nav-link {{ request()->routeIs($config['route'].'*') ? 'active' : '' }}"
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
