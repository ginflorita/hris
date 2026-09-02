<?php

namespace Tests\Feature\Admin;

use App\Enums\OffboardingStatus;
use App\Models\Employee;
use App\Models\OffboardingRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffboardingRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function hrAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['employees.view', 'employees.update']);

        return $user;
    }

    public function test_starting_offboarding(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.offboarding-requests.store', $employee), [
            'resignation_date' => '2026-03-01',
            'reason' => 'Pursuing an opportunity elsewhere.',
        ])->assertRedirect();

        $offboarding = OffboardingRequest::sole();
        $this->assertSame($employee->id, $offboarding->employee_id);
        $this->assertSame(OffboardingStatus::Resignation, $offboarding->status);
    }

    public function test_cannot_start_a_second_request_while_one_is_in_progress(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();
        OffboardingRequest::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->post(route('admin.employees.offboarding-requests.store', $employee), [
            'resignation_date' => '2026-03-01',
        ])->assertRedirect()->assertSessionHasErrors('offboarding');

        $this->assertSame(1, OffboardingRequest::count());
    }

    public function test_starting_a_new_request_after_the_prior_one_was_cancelled_is_allowed(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();
        OffboardingRequest::factory()->forEmployee($employee)->create(['status' => OffboardingStatus::Cancelled]);

        $this->actingAs($user)->post(route('admin.employees.offboarding-requests.store', $employee), [
            'resignation_date' => '2026-03-01',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame(2, OffboardingRequest::count());
    }

    public function test_advance_walks_through_the_full_sequence_and_disables_the_account_at_the_right_step(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();
        $accountUser = User::factory()->create(['employee_id' => $employee->id]);
        $offboarding = OffboardingRequest::factory()->forEmployee($employee)->create();

        $expectedSequence = array_slice(OffboardingStatus::sequence(), 1);

        foreach ($expectedSequence as $expectedStatus) {
            $this->actingAs($user)->put(route('admin.employees.offboarding-requests.advance', [$employee, $offboarding]))
                ->assertRedirect();

            $offboarding->refresh();
            $this->assertSame($expectedStatus, $offboarding->status);

            if ($expectedStatus === OffboardingStatus::Approved) {
                $this->assertNotNull($offboarding->approved_at);
                $this->assertSame($user->id, $offboarding->approved_by);
            }

            if ($expectedStatus === OffboardingStatus::AccountDisabled) {
                $this->assertNotNull($accountUser->refresh()->disabled_at);
            }
        }

        $this->assertSame(OffboardingStatus::Separated, $offboarding->status);
    }

    public function test_cannot_advance_a_separated_request(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();
        $offboarding = OffboardingRequest::factory()->forEmployee($employee)->create(['status' => OffboardingStatus::Separated]);

        $this->actingAs($user)->put(route('admin.employees.offboarding-requests.advance', [$employee, $offboarding]))
            ->assertStatus(422);
    }

    public function test_cancel_requires_a_reason_and_stops_further_advancement(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();
        $offboarding = OffboardingRequest::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->put(route('admin.employees.offboarding-requests.cancel', [$employee, $offboarding]), [])
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($user)->put(route('admin.employees.offboarding-requests.cancel', [$employee, $offboarding]), [
            'cancellation_reason' => 'Employee withdrew their resignation.',
        ])->assertRedirect();

        $offboarding->refresh();
        $this->assertSame(OffboardingStatus::Cancelled, $offboarding->status);
        $this->assertNotNull($offboarding->cancelled_at);

        $this->actingAs($user)->put(route('admin.employees.offboarding-requests.advance', [$employee, $offboarding]))
            ->assertStatus(422);
    }

    public function test_a_request_from_another_employee_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->hrAdmin();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $offboarding = OffboardingRequest::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->put(route('admin.employees.offboarding-requests.advance', [$employeeA, $offboarding]))
            ->assertNotFound();
    }

    public function test_index_lists_requests_across_employees(): void
    {
        $user = $this->hrAdmin();
        $offboarding = OffboardingRequest::factory()->create();

        $this->actingAs($user)->get(route('admin.offboarding-requests.index'))
            ->assertOk()
            ->assertSee($offboarding->employee->full_name);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->get(route('admin.offboarding-requests.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.employees.offboarding-requests.store', $employee), [])->assertForbidden();
    }
}
