<?php

use App\Http\Controllers\Admin\EmployeeAddressController;
use App\Http\Controllers\Admin\EmployeeContactController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeDependentController;
use App\Http\Controllers\Admin\EmployeeDocumentController;
use App\Http\Controllers\Admin\EmployeeEmergencyContactController;
use App\Http\Controllers\Admin\EmployeeGovernmentIdController;
use App\Http\Controllers\Admin\EmployeeNoteController;
use App\Http\Controllers\Admin\EmploymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::resource('employees', EmployeeController::class)->except('destroy');

        Route::prefix('employees/{employee}')->name('employees.')->group(function () {
            Route::put('archive', [EmployeeController::class, 'archive'])->name('archive');
            Route::put('restore', [EmployeeController::class, 'restore'])->name('restore');

            Route::post('addresses', [EmployeeAddressController::class, 'store'])->name('addresses.store');
            Route::put('addresses/{address}', [EmployeeAddressController::class, 'update'])->name('addresses.update');
            Route::delete('addresses/{address}', [EmployeeAddressController::class, 'destroy'])->name('addresses.destroy');

            Route::post('contacts', [EmployeeContactController::class, 'store'])->name('contacts.store');
            Route::put('contacts/{contact}', [EmployeeContactController::class, 'update'])->name('contacts.update');
            Route::delete('contacts/{contact}', [EmployeeContactController::class, 'destroy'])->name('contacts.destroy');

            Route::post('emergency-contacts', [EmployeeEmergencyContactController::class, 'store'])->name('emergency-contacts.store');
            Route::put('emergency-contacts/{emergencyContact}', [EmployeeEmergencyContactController::class, 'update'])->name('emergency-contacts.update');
            Route::delete('emergency-contacts/{emergencyContact}', [EmployeeEmergencyContactController::class, 'destroy'])->name('emergency-contacts.destroy');

            Route::post('government-ids', [EmployeeGovernmentIdController::class, 'store'])->name('government-ids.store');
            Route::put('government-ids/{governmentId}', [EmployeeGovernmentIdController::class, 'update'])->name('government-ids.update');
            Route::delete('government-ids/{governmentId}', [EmployeeGovernmentIdController::class, 'destroy'])->name('government-ids.destroy');

            Route::post('dependents', [EmployeeDependentController::class, 'store'])->name('dependents.store');
            Route::put('dependents/{dependent}', [EmployeeDependentController::class, 'update'])->name('dependents.update');
            Route::delete('dependents/{dependent}', [EmployeeDependentController::class, 'destroy'])->name('dependents.destroy');

            Route::post('documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
            Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
            Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');

            Route::post('notes', [EmployeeNoteController::class, 'store'])->name('notes.store');
            Route::put('notes/{note}', [EmployeeNoteController::class, 'update'])->name('notes.update');
            Route::delete('notes/{note}', [EmployeeNoteController::class, 'destroy'])->name('notes.destroy');

            Route::post('employments', [EmploymentController::class, 'store'])->name('employments.store');
        });
    });
