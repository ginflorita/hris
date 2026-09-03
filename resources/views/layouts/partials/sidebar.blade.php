{{--
    Mostly still static placeholders — most modules aren't built yet (see
    CLAUDE.md "Status"). WORKFORCE > Employees/Organization/Positions/COE
    Requests/Offboarding, TIME & ATTENDANCE > Attendance/Schedules/
    Shifts/Overtime/Holidays/Leave, PAYROLL > Compensation/Benefits,
    TALENT > Recruitment/Applicants/Onboarding/Performance/Training/
    Skills, REPORTS > Overview/HR Reports/Attendance Reports/Leave
    Reports, and ADMINISTRATION > Users/Roles/Permissions are real and
    permission-gated; the rest light up the same way as their phase
    lands.
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
            'WORKFORCE' => [
                'Employees' => ['route' => 'admin.employees.index', 'can' => ['employees.view']],
                'Organization' => [
                    'route' => 'admin.organization.companies.index',
                    'can' => ['organization.view'],
                    'active' => ['admin.organization.companies.*', 'admin.organization.branches.*', 'admin.organization.locations.*', 'admin.organization.divisions.*', 'admin.organization.departments.*', 'admin.organization.sections.*', 'admin.organization.teams.*'],
                ],
                'Positions' => [
                    'route' => 'admin.organization.positions.index',
                    'can' => ['organization.view'],
                    'active' => ['admin.organization.positions.*', 'admin.organization.job-levels.*', 'admin.organization.job-grades.*', 'admin.organization.cost-centers.*'],
                ],
                'Employment' => null,
                'Documents' => null,
                'COE Requests' => ['route' => 'admin.coe-requests.index', 'can' => ['employees.view']],
                'Offboarding' => ['route' => 'admin.offboarding-requests.index', 'can' => ['employees.view']],
            ],
            'TIME & ATTENDANCE' => [
                'Attendance' => ['route' => 'admin.attendance.attendances.index', 'can' => ['attendance.view']],
                'Schedules' => ['route' => 'admin.attendance.schedules.index', 'can' => ['attendance.view']],
                'Shifts' => ['route' => 'admin.attendance.shifts.index', 'can' => ['attendance.view']],
                'Overtime' => ['route' => 'admin.attendance.overtime.index', 'can' => ['attendance.view']],
                'Holidays' => ['route' => 'admin.attendance.holidays.index', 'can' => ['attendance.view']],
                'Leave' => [
                    'route' => 'admin.leave.requests.index',
                    'can' => ['leave.view'],
                    'active' => ['admin.leave.*'],
                ],
            ],
            'PAYROLL' => [
                'Payroll' => null,
                'Payroll Periods' => [
                    'route' => 'admin.payroll.payroll-periods.index',
                    'can' => ['payroll.view'],
                    'active' => ['admin.payroll.payroll-periods.*'],
                ],
                'Payroll Groups' => [
                    'route' => 'admin.payroll.payroll-groups.index',
                    'can' => ['payroll.view'],
                    'active' => ['admin.payroll.payroll-groups.*'],
                ],
                'Government Rates' => [
                    'route' => 'admin.payroll.contribution-rate-tables.index',
                    'can' => ['payroll.view'],
                    'active' => ['admin.payroll.contribution-rate-tables.*', 'admin.payroll.tax-tables.*'],
                ],
                'Compensation' => [
                    'route' => 'admin.compensation.structures.index',
                    'can' => ['organization.view'],
                    'active' => ['admin.compensation.*'],
                ],
                'Benefits' => [
                    'route' => 'admin.benefits.plans.index',
                    'can' => ['benefits.view'],
                    'active' => ['admin.benefits.*'],
                ],
                'Payslips' => null,
            ],
            'TALENT' => [
                'Recruitment' => [
                    'route' => 'admin.recruitment.requisitions.index',
                    'can' => ['recruitment.view'],
                    'active' => ['admin.recruitment.requisitions.*', 'admin.recruitment.postings.*'],
                ],
                'Applicants' => [
                    'route' => 'admin.recruitment.applicants.index',
                    'can' => ['recruitment.view'],
                    'active' => ['admin.recruitment.applicants.*', 'admin.recruitment.applications.*'],
                ],
                'Onboarding' => [
                    'route' => 'admin.recruitment.onboarding-templates.index',
                    'can' => ['recruitment.view'],
                    'active' => ['admin.recruitment.onboarding-templates.*'],
                ],
                'Performance' => [
                    'route' => 'admin.performance.cycles.index',
                    'can' => ['performance.view'],
                    'active' => ['admin.performance.*'],
                ],
                'Training' => [
                    'route' => 'admin.training.courses.index',
                    'can' => ['training.view'],
                    'active' => ['admin.training.courses.*', 'admin.training.providers.*'],
                ],
                'Skills' => [
                    'route' => 'admin.training.competencies.index',
                    'can' => ['training.view'],
                    'active' => ['admin.training.competencies.*', 'admin.training.skills.*'],
                ],
                'Career' => null,
            ],
            'REPORTS' => [
                'Overview' => ['route' => 'admin.reports.index', 'can' => ['reports.view']],
                'HR Reports' => ['route' => 'admin.reports.hr.index', 'can' => ['reports.view']],
                'Attendance Reports' => ['route' => 'admin.attendance.report.index', 'can' => ['attendance.view']],
                'Leave Reports' => ['route' => 'admin.leave.report.index', 'can' => ['leave.view']],
                'Payroll Reports' => null,
                'Analytics' => null,
            ],
            'ADMINISTRATION' => [
                'Users' => ['route' => 'admin.users.index', 'can' => ['viewAny', \App\Models\User::class]],
                'Roles' => ['route' => 'admin.roles.index', 'can' => ['viewAny', \App\Models\Role::class]],
                'Permissions' => ['route' => 'admin.permissions.index', 'can' => ['viewAny', \App\Models\Role::class]],
                'Workflows' => null,
                'Notifications' => null,
                'Announcements' => null,
                'Audit Logs' => ['route' => 'admin.audit-logs.index', 'can' => ['audit-logs.view']],
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
                            <a class="nav-link {{ request()->routeIs(...($config['active'] ?? [$config['route'].'*'])) ? 'active' : '' }}"
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
