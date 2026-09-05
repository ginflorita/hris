<?php

namespace Tests\Feature\Admin\Reports;

use App\Models\Company;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function performanceAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('performance.view');

        return $user;
    }

    public function test_performance_report_requires_performance_view_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user)->get(route('admin.reports.performance.index'))->assertForbidden();
        $this->actingAs($this->performanceAdmin())->get(route('admin.reports.performance.index'))->assertOk();
    }

    public function test_performance_report_computes_average_rating_and_goal_completion_for_the_selected_cycle(): void
    {
        $company = Company::factory()->create();
        $cycle = PerformanceCycle::factory()->for($company, 'company')->create();

        PerformanceReview::factory()->for($cycle, 'performanceCycle')->create(['type' => 'manager', 'rating' => 4]);
        PerformanceReview::factory()->for($cycle, 'performanceCycle')->create(['type' => 'self', 'rating' => 2]);
        PerformanceReview::factory()->for($cycle, 'performanceCycle')->create(['type' => 'peer', 'rating' => null]);

        PerformanceGoal::factory()->for($cycle, 'performanceCycle')->create(['status' => 'completed']);
        PerformanceGoal::factory()->for($cycle, 'performanceCycle')->create(['status' => 'in_progress']);

        $this->actingAs($this->performanceAdmin())
            ->get(route('admin.reports.performance.index', ['performance_cycle_id' => $cycle->id]))
            ->assertOk()
            ->assertViewHas('totalReviews', 3)
            ->assertViewHas('averageRating', 3.0)
            ->assertViewHas('totalGoals', 2)
            ->assertViewHas('completedGoals', 1)
            ->assertViewHas('goalCompletionRate', 50.0);
    }

    public function test_performance_report_breaks_a_tied_start_date_by_the_newest_cycle(): void
    {
        $company = Company::factory()->create();
        $older = PerformanceCycle::factory()->for($company, 'company')->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $newer = PerformanceCycle::factory()->for($company, 'company')->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

        $this->actingAs($this->performanceAdmin())
            ->get(route('admin.reports.performance.index'))
            ->assertOk()
            ->assertViewHas('selectedCycle', fn ($cycle) => $cycle->id === $newer->id);
    }

    public function test_performance_report_defaults_to_the_most_recent_cycle_when_none_is_selected(): void
    {
        $company = Company::factory()->create();
        $older = PerformanceCycle::factory()->for($company, 'company')->create(['start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $newer = PerformanceCycle::factory()->for($company, 'company')->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

        $this->actingAs($this->performanceAdmin())
            ->get(route('admin.reports.performance.index'))
            ->assertOk()
            ->assertViewHas('selectedCycle', fn ($cycle) => $cycle->id === $newer->id);
    }

    public function test_performance_report_handles_a_company_with_no_cycles(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->performanceAdmin())
            ->get(route('admin.reports.performance.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewHas('selectedCycle', fn ($cycle) => $cycle === null);
    }
}
