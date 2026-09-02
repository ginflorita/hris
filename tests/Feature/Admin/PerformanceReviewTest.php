<?php

namespace Tests\Feature\Admin;

use App\Enums\PerformanceReviewStatus;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['performance.view', 'performance.manage']);

        return $user;
    }

    public function test_adding_a_self_review(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $employee->id,
            'type' => 'self',
            'rating' => 4,
            'comments' => 'Met most goals.',
        ])->assertRedirect();

        $review = PerformanceReview::sole();
        $this->assertSame($employee->id, $review->employee_id);
        $this->assertSame($employee->id, $review->reviewer_id);
        $this->assertSame(PerformanceReviewStatus::Draft, $review->status);
    }

    public function test_a_self_review_must_name_the_employee_as_reviewer(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create(['company_id' => $employee->company_id]);
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $other->id,
            'type' => 'self',
        ])->assertStatus(422);
    }

    public function test_a_manager_review_cannot_name_the_employee_as_their_own_reviewer(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $employee->id,
            'type' => 'manager',
        ])->assertStatus(422);
    }

    public function test_only_one_manager_review_per_cycle_but_peer_reviews_are_unrestricted(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);
        $managerReviewer = Employee::factory()->create(['company_id' => $employee->company_id]);
        $peerA = Employee::factory()->create(['company_id' => $employee->company_id]);
        $peerB = Employee::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $managerReviewer->id,
            'type' => 'manager',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $managerReviewer->id,
            'type' => 'manager',
        ])->assertSessionHasErrors('type');

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $peerA->id,
            'type' => 'peer',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $peerB->id,
            'type' => 'peer',
        ])->assertRedirect();

        $this->assertSame(3, PerformanceReview::count());
    }

    public function test_cycle_and_reviewer_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $wrongCycle = PerformanceCycle::factory()->create();
        $wrongReviewer = Employee::factory()->create();
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $wrongCycle->id,
            'reviewer_id' => $wrongReviewer->id,
            'type' => 'peer',
        ])->assertSessionHasErrors(['performance_cycle_id', 'reviewer_id']);

        $this->actingAs($user)->post(route('admin.employees.performance-reviews.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'reviewer_id' => $wrongReviewer->id,
            'type' => 'peer',
        ])->assertSessionHasErrors('reviewer_id');
    }

    public function test_submit_requires_a_rating_then_moves_to_submitted(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $review = PerformanceReview::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->put(route('admin.employees.performance-reviews.submit', [$employee, $review]))
            ->assertStatus(422);

        $review->update(['rating' => 3]);

        $this->actingAs($user)->put(route('admin.employees.performance-reviews.submit', [$employee, $review]))
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(PerformanceReviewStatus::Submitted, $review->status);
        $this->assertNotNull($review->submitted_at);
    }

    public function test_full_lifecycle_locks_editing_and_deletion(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $review = PerformanceReview::factory()->forEmployee($employee)->create(['rating' => 5]);

        $this->actingAs($user)->put(route('admin.employees.performance-reviews.submit', [$employee, $review]))->assertRedirect();

        // A submitted review can no longer be edited or deleted.
        $this->actingAs($user)->put(route('admin.employees.performance-reviews.update', [$employee, $review]), [
            'performance_cycle_id' => $review->performance_cycle_id,
            'reviewer_id' => $review->reviewer_id,
            'type' => $review->type->value,
        ])->assertStatus(422);

        $this->actingAs($user)->delete(route('admin.employees.performance-reviews.destroy', [$employee, $review]))
            ->assertStatus(422);

        $this->actingAs($user)->put(route('admin.employees.performance-reviews.acknowledge', [$employee, $review]))
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(PerformanceReviewStatus::Acknowledged, $review->status);
        $this->assertNotNull($review->acknowledged_at);

        // Cannot acknowledge twice.
        $this->actingAs($user)->put(route('admin.employees.performance-reviews.acknowledge', [$employee, $review]))
            ->assertStatus(422);
    }

    public function test_a_review_from_another_employee_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->manager();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $review = PerformanceReview::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->put(route('admin.employees.performance-reviews.submit', [$employeeA, $review]))
            ->assertNotFound();
    }

    public function test_removing_a_draft_review(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $review = PerformanceReview::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->delete(route('admin.employees.performance-reviews.destroy', [$employee, $review]))
            ->assertRedirect();

        $this->assertSame(0, PerformanceReview::count());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.performance-reviews.store', $employee), [])->assertForbidden();
    }
}
