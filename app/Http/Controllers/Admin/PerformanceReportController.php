<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerformanceGoalStatus;
use App\Enums\PerformanceReviewType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §3 item 58, "Performance Reports". Same "reachable from the
 * Reports landing page only, no new sidebar row" shape as Recruitment
 * Report -- see that controller's doc comment. Gated by
 * `performance.view`.
 */
class PerformanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('performance.view');

        $companyId = $request->integer('company_id');

        $cycles = PerformanceCycle::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $selectedCycleId = $request->integer('performance_cycle_id');
        $selectedCycle = $cycles->firstWhere('id', $selectedCycleId) ?? $cycles->first();

        $reviews = $selectedCycle
            ? PerformanceReview::where('performance_cycle_id', $selectedCycle->id)->get()
            : collect();

        $goals = $selectedCycle
            ? PerformanceGoal::where('performance_cycle_id', $selectedCycle->id)->get()
            : collect();

        $ratedReviews = $reviews->whereNotNull('rating');

        $byReviewType = collect(PerformanceReviewType::cases())->map(fn (PerformanceReviewType $type) => [
            'type' => $type,
            'count' => $reviews->where('type', $type)->count(),
        ]);

        $completedGoals = $goals->where('status', PerformanceGoalStatus::Completed)->count();

        $recentCycles = $cycles->take(6)->map(fn (PerformanceCycle $cycle) => [
            'cycle' => $cycle,
            'averageRating' => round((float) PerformanceReview::where('performance_cycle_id', $cycle->id)->whereNotNull('rating')->avg('rating'), 2),
        ]);

        return view('admin.reports.performance.index', [
            'companies' => Company::orderBy('name')->get(),
            'companyId' => $companyId,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'totalReviews' => $reviews->count(),
            'averageRating' => $ratedReviews->isNotEmpty() ? round($ratedReviews->avg('rating'), 2) : null,
            'byReviewType' => $byReviewType,
            'totalGoals' => $goals->count(),
            'completedGoals' => $completedGoals,
            'goalCompletionRate' => $goals->isNotEmpty() ? round($completedGoals / $goals->count() * 100, 1) : null,
            'recentCycles' => $recentCycles,
        ]);
    }
}
