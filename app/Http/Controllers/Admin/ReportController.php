<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Blueprint §3 lists eight report/analytics modules (53-60: HR, Payroll,
 * Attendance, Leave, Recruitment, Performance, Training Reports, plus
 * Workforce Analytics) and §55's own "V1 MVP" list names Reports as
 * item 19 -- but blueprint never gives Reports a numbered detail
 * section (confirmed by grep before designing this: only "Workflow
 * Engine" has one) and never assigns it to one of §54's Phase 1-18,
 * despite listing it as V1-scoped. This landing page is the front door;
 * each report type still gets its own controller and route, the same
 * shape Attendance (Phase 8) and Leave (Phase 9) already established
 * for their own report pages -- this phase doesn't change those, it
 * gives them (and the new ones) one place to be found from, gated by
 * the long-reserved `reports.view` permission nothing has checked
 * until now.
 */
class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('reports.view');

        return view('admin.reports.index');
    }
}
