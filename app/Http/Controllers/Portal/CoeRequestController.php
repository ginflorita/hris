<?php

namespace App\Http\Controllers\Portal;

use App\Enums\CoeRequestStatus;
use App\Enums\CoeRequestType;
use App\Http\Controllers\Controller;
use App\Models\CoeRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §18 "Request COE". download() reuses the same
 * documents.coe-pdf template and frozen snapshot columns as the
 * admin-side download in Admin\CoeRequestController -- there's exactly
 * one certificate per approved request, not a separate "portal copy".
 */
class CoeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        if ($employee) {
            $employee->load(['coeRequests' => fn ($q) => $q->orderByDesc('created_at')]);
        }

        return view('portal.coe.index', ['employee' => $employee]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(CoeRequestType::class)],
            'purpose' => ['nullable', 'string', 'max:255'],
        ]);

        CoeRequest::create([
            ...$validated,
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'status' => CoeRequestStatus::Pending,
            'requested_by' => $request->user()->id,
        ]);

        return back()->with('status', 'COE request submitted.');
    }

    public function download(Request $request, CoeRequest $coeRequest): Response
    {
        abort_unless($coeRequest->employee_id === $request->user()->employee_id, 404);
        abort_unless($coeRequest->status === CoeRequestStatus::Approved, 404);

        $coeRequest->load(['employee', 'company']);
        $pdf = Pdf::loadView('documents.coe-pdf', ['coeRequest' => $coeRequest]);

        return $pdf->download('coe-'.$coeRequest->employee->employee_number.'.pdf');
    }
}
