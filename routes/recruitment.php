<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\JobPostingController;
use App\Http\Controllers\Admin\JobRequisitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/recruitment')->name('admin.recruitment.')->group(function () {
        Route::resource('requisitions', JobRequisitionController::class)->only(['index', 'create', 'store']);
        Route::put('requisitions/{requisition}/approve', [JobRequisitionController::class, 'approve'])->name('requisitions.approve');
        Route::put('requisitions/{requisition}/reject', [JobRequisitionController::class, 'reject'])->name('requisitions.reject');

        Route::resource('postings', JobPostingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::put('postings/{posting}/publish', [JobPostingController::class, 'publish'])->name('postings.publish');
        Route::put('postings/{posting}/close', [JobPostingController::class, 'close'])->name('postings.close');

        Route::resource('applicants', ApplicantController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::get('applicants/{applicant}/resume', [ApplicantController::class, 'downloadResume'])->name('applicants.resume');
        Route::post('applicants/{applicant}/applications', [ApplicationController::class, 'store'])->name('applicants.applications.store');

        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::put('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status');
    });
