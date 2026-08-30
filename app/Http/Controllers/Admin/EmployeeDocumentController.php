<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $validated = $request->validate([
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $request->file('file');
        $path = $file->store("employee-documents/{$employee->id}", 'local');

        $employee->documents()->create([
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Document uploaded.');
    }

    public function download(Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        $this->authorize('employees.view');
        abort_unless($document->employee_id === $employee->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function destroy(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($document->employee_id === $employee->id, 404);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('status', 'Document removed.');
    }
}
