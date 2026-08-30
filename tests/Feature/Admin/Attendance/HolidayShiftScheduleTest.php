<?php

namespace Tests\Feature\Admin\Attendance;

use App\Models\Company;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayShiftScheduleTest extends TestCase
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
        $user->givePermissionTo(['attendance.view', 'attendance.manage']);

        return $user;
    }

    public function test_holiday_crud_and_date_uniqueness_per_company(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.holidays.store'), [
            'company_id' => $company->id,
            'name' => "New Year's Day",
            'date' => '2026-01-01',
            'type' => 'regular',
        ])->assertRedirect(route('admin.attendance.holidays.index'));

        $holiday = Holiday::sole();
        $this->assertTrue($holiday->is_active);

        $this->actingAs($user)->post(route('admin.attendance.holidays.store'), [
            'company_id' => $company->id,
            'name' => 'Duplicate',
            'date' => '2026-01-01',
            'type' => 'special_non_working',
        ])->assertSessionHasErrors('date');

        $this->actingAs($user)->delete(route('admin.attendance.holidays.destroy', $holiday))
            ->assertRedirect(route('admin.attendance.holidays.index'));
        $this->assertSoftDeleted($holiday);
    }

    public function test_shift_crud_and_cannot_delete_while_referenced_by_a_schedule(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.shifts.store'), [
            'company_id' => $company->id,
            'name' => 'Day Shift',
            'code' => 'DAY',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'grace_minutes' => 10,
        ])->assertRedirect(route('admin.attendance.shifts.index'));

        $shift = Shift::sole();
        $this->assertTrue($shift->is_active);

        Schedule::factory()->for($company, 'company')->create(['shift_id' => $shift->id]);

        $this->actingAs($user)->delete(route('admin.attendance.shifts.destroy', $shift))
            ->assertSessionHasErrors('shift');
    }

    public function test_schedule_crud_with_rest_days_and_optional_shift(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $shift = Shift::factory()->for($companyA, 'company')->create();

        // Shift from a different company is rejected.
        $this->actingAs($user)->post(route('admin.attendance.schedules.store'), [
            'company_id' => $companyB->id,
            'shift_id' => $shift->id,
            'name' => 'Mismatched',
            'code' => 'MM',
            'type' => 'fixed',
        ])->assertSessionHasErrors('shift_id');

        $this->actingAs($user)->post(route('admin.attendance.schedules.store'), [
            'company_id' => $companyA->id,
            'shift_id' => $shift->id,
            'name' => 'Weekday Schedule',
            'code' => 'WD',
            'type' => 'fixed',
            'rest_days' => [0, 6],
        ])->assertRedirect(route('admin.attendance.schedules.index'));

        $schedule = Schedule::sole();
        $this->assertSame([0, 6], $schedule->rest_days);
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.attendance.holidays.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.attendance.shifts.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.attendance.schedules.index'))->assertForbidden();
    }
}
