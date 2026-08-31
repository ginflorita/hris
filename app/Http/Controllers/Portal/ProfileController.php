<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only self-service (blueprint §18 "View profile" / "View
 * employment" / "View documents"). Updating "permitted information" is
 * a real §18 bullet but isn't built yet -- see CLAUDE.md "Employee
 * Self-Service" for why that's a deliberate, separate slice rather than
 * bolted on here.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $employee = $request->user()->employee;

        if ($employee) {
            $employee->load([
                'company',
                'employments' => fn ($q) => $q->with(['position', 'department', 'salaryGrade', 'branch', 'manager'])->orderByDesc('effective_date'),
                'documents.uploadedBy',
            ]);
        }

        return view('portal.profile.show', ['employee' => $employee]);
    }

    public function downloadDocument(Request $request, EmployeeDocument $document): StreamedResponse
    {
        abort_unless($document->employee_id === $request->user()->employee_id, 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }
}
