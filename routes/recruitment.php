<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\InterviewController;
use App\Http\Controllers\Admin\JobOfferController;
use App\Http\Controllers\Admin\JobPostingController;
use App\Http\Controllers\Admin\JobRequisitionController;
use App\Http\Controllers\Admin\OnboardingTaskController;
use App\Http\Controllers\Admin\OnboardingTemplateController;
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
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::put('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status');

        Route::post('applications/{application}/interviews', [InterviewController::class, 'store'])->name('applications.interviews.store');
        Route::put('applications/{application}/interviews/{interview}', [InterviewController::class, 'update'])->name('applications.interviews.update');

        Route::post('applications/{application}/assessments', [AssessmentController::class, 'store'])->name('applications.assessments.store');
        Route::put('applications/{application}/assessments/{assessment}', [AssessmentController::class, 'update'])->name('applications.assessments.update');

        Route::post('applications/{application}/offers', [JobOfferController::class, 'store'])->name('applications.offers.store');
        Route::put('applications/{application}/offers/{offer}/accept', [JobOfferController::class, 'accept'])->name('applications.offers.accept');
        Route::put('applications/{application}/offers/{offer}/decline', [JobOfferController::class, 'decline'])->name('applications.offers.decline');
        Route::put('applications/{application}/offers/{offer}/rescind', [JobOfferController::class, 'rescind'])->name('applications.offers.rescind');
        Route::get('applications/{application}/offers/{offer}/convert', [JobOfferController::class, 'convertForm'])->name('applications.offers.convert-form');
        Route::post('applications/{application}/offers/{offer}/convert', [JobOfferController::class, 'convert'])->name('applications.offers.convert');

        Route::resource('onboarding-templates', OnboardingTemplateController::class);
        Route::post('onboarding-templates/{onboardingTemplate}/tasks', [OnboardingTaskController::class, 'store'])->name('onboarding-templates.tasks.store');
        Route::put('onboarding-templates/{onboardingTemplate}/tasks/{task}', [OnboardingTaskController::class, 'update'])->name('onboarding-templates.tasks.update');
        Route::delete('onboarding-templates/{onboardingTemplate}/tasks/{task}', [OnboardingTaskController::class, 'destroy'])->name('onboarding-templates.tasks.destroy');
    });
