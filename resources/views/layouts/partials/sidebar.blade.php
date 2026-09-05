{{--
    Mostly still static placeholders — most modules aren't built yet (see
    CLAUDE.md "Status"). WORKFORCE > Employees/Organization/Positions/COE
    Requests/Offboarding, TIME & ATTENDANCE > Attendance/Schedules/
    Shifts/Overtime/Holidays/Leave, PAYROLL > Compensation/Benefits,
    TALENT > Recruitment/Applicants/Onboarding/Performance/Training/
    Skills, REPORTS (all five items), and ADMINISTRATION > Users/Roles/
    Permissions/Workflows are real and permission-gated; the rest light
    up the same way as their phase lands.

    Collapse-to-icons (desktop only, >= lg): `#sidebar-collapse-toggle`
    (in layouts/partials/topbar.blade.php — `.offcanvas-header` in here
    is force-hidden by Bootstrap's own `.offcanvas-lg` responsive CSS
    above the breakpoint, so a toggle placed in it could never show at
    the one width where it needs to) flips a `data-sidebar="collapsed"`
    attribute on <html> via
    resources/js/sidebar-collapse.js, persisted in localStorage the same
    way resources/js/color-modes.js persists the light/dark/system theme;
    a matching pre-paint snippet in layouts/partials/head.blade.php sets
    the attribute before first render to avoid a flash of the wrong
    width. _layout.scss's `[data-sidebar="collapsed"]` rules (scoped to
    the same >= 992px breakpoint the fixed sidebar itself uses) hide
    `.nav-label`/section titles and shrink `.app-sidebar` to
    $sidebar-width-collapsed — the mobile offcanvas is unaffected, since
    collapsing an already-hidden-until-opened overlay to icon-only would
    just make it harder to use. Every link keeps a plain `title`
    attribute so the label is still available (native browser tooltip)
    once collapsed, without pulling in Bootstrap's JS Tooltip component
    for something this simple.
--}}
<div class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <span class="offcanvas-title fw-semibold" id="appSidebarLabel">HRIS</span>
        <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-2">
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}" title="Dashboard">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
        </ul>

        @foreach ([
            'WORKFORCE' => [
                'Employees' => ['icon' => 'people', 'route' => 'admin.employees.index', 'can' => ['employees.view']],
                'Organization' => [
                    'icon' => 'building',
                    'route' => 'admin.organization.companies.index',
                    'can' => ['organization.view'],
                    'active' => ['admin.organization.companies.*', 'admin.organization.branches.*', 'admin.organization.locations.*', 'admin.organization.divisions.*', 'admin.organization.departments.*', 'admin.organization.sections.*', 'admin.organization.teams.*'],
                ],
                'Positions' => [
                    'icon' => 'diagram-3',
                    'route' => 'admin.organization.positions.index',
                    'can' => ['organization.view'],
                    'active' => ['admin.organization.positions.*', 'admin.organization.job-levels.*', 'admin.organization.job-grades.*', 'admin.organization.cost-centers.*'],
                ],
                'Employment' => ['icon' => 'briefcase'],
                'Documents' => ['icon' => 'file-earmark-text'],
                'COE Requests' => ['icon' => 'file-earmark-check', 'route' => 'admin.coe-requests.index', 'can' => ['employees.view']],
                'Offboarding' => ['icon' => 'box-arrow-right', 'route' => 'admin.offboarding-requests.index', 'can' => ['employees.view']],
            ],
            'TIME & ATTENDANCE' => [
                'Attendance' => ['icon' => 'clock-history', 'route' => 'admin.attendance.attendances.index', 'can' => ['attendance.view']],
                'Schedules' => ['icon' => 'calendar3', 'route' => 'admin.attendance.schedules.index', 'can' => ['attendance.view']],
                'Shifts' => ['icon' => 'arrow-repeat', 'route' => 'admin.attendance.shifts.index', 'can' => ['attendance.view']],
                'Overtime' => ['icon' => 'alarm', 'route' => 'admin.attendance.overtime.index', 'can' => ['attendance.view']],
                'Holidays' => ['icon' => 'calendar-event', 'route' => 'admin.attendance.holidays.index', 'can' => ['attendance.view']],
                'Leave' => [
                    'icon' => 'airplane',
                    'route' => 'admin.leave.requests.index',
                    'can' => ['leave.view'],
                    'active' => ['admin.leave.*'],
                ],
            ],
            'PAYROLL' => [
                'Payroll' => ['icon' => 'cash-coin'],
                'Payroll Periods' => [
                    'icon' => 'calendar-range',
                    'route' => 'admin.payroll.payroll-periods.index',
                    'can' => ['payroll.view'],
                    'active' => ['admin.payroll.payroll-periods.*'],
                ],
                'Payroll Groups' => [
                    'icon' => 'collection',
                    'route' => 'admin.payroll.payroll-groups.index',
                    'can' => ['payroll.view'],
                    'active' => ['admin.payroll.payroll-groups.*'],
                ],
                'Government Rates' => [
                    'icon' => 'bank',
                    'route' => 'admin.payroll.contribution-rate-tables.index',
                    'can' => ['payroll.view'],
                    'active' => ['admin.payroll.contribution-rate-tables.*', 'admin.payroll.tax-tables.*'],
                ],
                'Compensation' => [
                    'icon' => 'wallet2',
                    'route' => 'admin.compensation.structures.index',
                    'can' => ['organization.view'],
                    'active' => ['admin.compensation.*'],
                ],
                'Benefits' => [
                    'icon' => 'heart-pulse',
                    'route' => 'admin.benefits.plans.index',
                    'can' => ['benefits.view'],
                    'active' => ['admin.benefits.*'],
                ],
                'Payslips' => ['icon' => 'receipt'],
            ],
            'TALENT' => [
                'Recruitment' => [
                    'icon' => 'person-plus',
                    'route' => 'admin.recruitment.requisitions.index',
                    'can' => ['recruitment.view'],
                    'active' => ['admin.recruitment.requisitions.*', 'admin.recruitment.postings.*'],
                ],
                'Applicants' => [
                    'icon' => 'person-lines-fill',
                    'route' => 'admin.recruitment.applicants.index',
                    'can' => ['recruitment.view'],
                    'active' => ['admin.recruitment.applicants.*', 'admin.recruitment.applications.*'],
                ],
                'Onboarding' => [
                    'icon' => 'clipboard-check',
                    'route' => 'admin.recruitment.onboarding-templates.index',
                    'can' => ['recruitment.view'],
                    'active' => ['admin.recruitment.onboarding-templates.*'],
                ],
                'Performance' => [
                    'icon' => 'graph-up',
                    'route' => 'admin.performance.cycles.index',
                    'can' => ['performance.view'],
                    'active' => ['admin.performance.*'],
                ],
                'Training' => [
                    'icon' => 'mortarboard',
                    'route' => 'admin.training.courses.index',
                    'can' => ['training.view'],
                    'active' => ['admin.training.courses.*', 'admin.training.providers.*'],
                ],
                'Skills' => [
                    'icon' => 'award',
                    'route' => 'admin.training.competencies.index',
                    'can' => ['training.view'],
                    'active' => ['admin.training.competencies.*', 'admin.training.skills.*'],
                ],
                'Career' => ['icon' => 'signpost-2'],
            ],
            'REPORTS' => [
                'Overview' => ['icon' => 'grid-1x2', 'route' => 'admin.reports.index', 'can' => ['reports.view']],
                'HR Reports' => ['icon' => 'people-fill', 'route' => 'admin.reports.hr.index', 'can' => ['reports.view']],
                'Attendance Reports' => ['icon' => 'bar-chart', 'route' => 'admin.attendance.report.index', 'can' => ['attendance.view']],
                'Leave Reports' => ['icon' => 'pie-chart', 'route' => 'admin.leave.report.index', 'can' => ['leave.view']],
                'Payroll Reports' => ['icon' => 'cash-stack', 'route' => 'admin.reports.payroll.index', 'can' => ['payroll.view']],
                'Analytics' => ['icon' => 'graph-up-arrow', 'route' => 'admin.reports.analytics.index', 'can' => ['reports.view']],
            ],
            'ADMINISTRATION' => [
                'Users' => ['icon' => 'person-gear', 'route' => 'admin.users.index', 'can' => ['viewAny', \App\Models\User::class]],
                'Roles' => ['icon' => 'shield-lock', 'route' => 'admin.roles.index', 'can' => ['viewAny', \App\Models\Role::class]],
                'Permissions' => ['icon' => 'key', 'route' => 'admin.permissions.index', 'can' => ['viewAny', \App\Models\Role::class]],
                'Workflows' => ['icon' => 'diagram-2', 'route' => 'admin.workflow.definitions.index', 'can' => ['workflow.view']],
                'Notifications' => ['icon' => 'bell'],
                'Announcements' => ['icon' => 'megaphone'],
                'Audit Logs' => ['icon' => 'journal-text', 'route' => 'admin.audit-logs.index', 'can' => ['audit-logs.view']],
                'Settings' => ['icon' => 'gear'],
            ],
        ] as $section => $items)
            <div class="nav-section-title">{{ $section }}</div>
            <ul class="nav nav-pills flex-column mb-2">
                @foreach ($items as $label => $config)
                    <li class="nav-item">
                        @if (isset($config['route']) && Route::has($config['route']) && auth()->user()?->can(...$config['can']))
                            <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs(...($config['active'] ?? [$config['route'].'*'])) ? 'active' : '' }}"
                               href="{{ route($config['route']) }}" title="{{ $label }}">
                                <i class="bi bi-{{ $config['icon'] }}" aria-hidden="true"></i>
                                <span class="nav-label">{{ $label }}</span>
                            </a>
                        @else
                            <span class="nav-link disabled d-flex align-items-center gap-2 text-body-secondary" aria-disabled="true" title="{{ $label }}">
                                <i class="bi bi-{{ $config['icon'] }}" aria-hidden="true"></i>
                                <span class="nav-label">{{ $label }}</span>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>
