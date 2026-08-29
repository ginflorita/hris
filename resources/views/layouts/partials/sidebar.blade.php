{{--
    Static nav for now — every item other than Dashboard points nowhere
    because the underlying module hasn't been built yet (see CLAUDE.md
    "Status"). Once RBAC (Phase 4) exists, this needs to filter sections
    by the current user's permissions rather than listing everything.
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
            'ADMINISTRATION' => ['Users', 'Roles', 'Permissions', 'Workflows', 'Notifications', 'Announcements', 'Audit Logs', 'Security', 'Settings'],
        ] as $section => $items)
            <div class="nav-section-title">{{ $section }}</div>
            <ul class="nav nav-pills flex-column mb-2">
                @foreach ($items as $item)
                    <li class="nav-item">
                        <span class="nav-link disabled text-body-secondary" aria-disabled="true">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>
