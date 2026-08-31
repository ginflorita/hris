<?php

use App\Http\Controllers\Admin\ContributionRateBracketController;
use App\Http\Controllers\Admin\ContributionRateTableController;
use App\Http\Controllers\Admin\TaxTableBracketController;
use App\Http\Controllers\Admin\TaxTableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/payroll')->name('admin.payroll.')->group(function () {
        Route::resource('contribution-rate-tables', ContributionRateTableController::class);
        Route::post('contribution-rate-tables/{contribution_rate_table}/brackets', [ContributionRateBracketController::class, 'store'])
            ->name('contribution-rate-tables.brackets.store');
        Route::put('contribution-rate-tables/{contribution_rate_table}/brackets/{bracket}', [ContributionRateBracketController::class, 'update'])
            ->name('contribution-rate-tables.brackets.update');
        Route::delete('contribution-rate-tables/{contribution_rate_table}/brackets/{bracket}', [ContributionRateBracketController::class, 'destroy'])
            ->name('contribution-rate-tables.brackets.destroy');

        Route::resource('tax-tables', TaxTableController::class);
        Route::post('tax-tables/{tax_table}/brackets', [TaxTableBracketController::class, 'store'])
            ->name('tax-tables.brackets.store');
        Route::put('tax-tables/{tax_table}/brackets/{bracket}', [TaxTableBracketController::class, 'update'])
            ->name('tax-tables.brackets.update');
        Route::delete('tax-tables/{tax_table}/brackets/{bracket}', [TaxTableBracketController::class, 'destroy'])
            ->name('tax-tables.brackets.destroy');
    });
