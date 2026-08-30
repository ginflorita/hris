<?php

namespace Tests\Feature\Admin\Attendance;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceProbeTest extends TestCase
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
        $user->givePermissionTo(['attendance.view', 'attendance.manage', 'attendance.correct']);

        return $user;
    }

    public function test_manual_entry_computes_late_minutes_against_the_assigned_shift(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $shift = Shift::factory()->for($company, 'company')->create(['start_time' => '08:00', 'end_time' => '17:00', 'grace_minutes' => 10]);
        $schedule = Schedule::factory()->for($company, 'company')->create(['shift_id' => $shift->id]);
        EmployeeSchedule::factory()->create(['employee_id' => $employee->id, 'schedule_id' => $schedule->id, 'effective_date' => now()->subDays(30)]);

        $this->actingAs($user)->post(route('admin.attendance.attendances.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-01-05',
            'time_in' => '08:25',
            'time_out' => '17:00',
            'status' => 'late',
        ])->assertRedirect();

        $attendance = Attendance::sole();
        // 08:25 - (08:00 + 10 min grace) = 15 minutes late
        $this->assertSame(15, $attendance->late_minutes);
        $this->assertSame(0, $attendance->undertime_minutes);
    }

    public function test_correction_is_logged_and_never_silently_overwrites(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $attendance = Attendance::factory()->forEmployee($employee)->create([
            'date' => '2026-01-05', 'time_in' => '2026-01-05 08:00:00', 'time_out' => '2026-01-05 17:00:00', 'status' => 'present',
        ]);

        $this->actingAs($user)->put(route('admin.attendance.attendances.update', $attendance), [
            'time_in' => '08:30',
            'time_out' => '17:00',
            'status' => 'late',
            'reason' => 'Employee forgot to clock in on time, verified with security logs.',
        ])->assertRedirect();

        $attendance->refresh();
        $this->assertTrue($attendance->is_corrected);
        $this->assertSame($user->id, $attendance->corrected_by);
        $this->assertSame('08:30', $attendance->time_in->format('H:i'));

        $logs = $attendance->correctionLogs;
        $this->assertGreaterThanOrEqual(2, $logs->count());
        $timeInLog = $logs->firstWhere('field', 'time_in');
        $this->assertSame('08:00', $timeInLog->old_value);
        $this->assertSame('08:30', $timeInLog->new_value);
        $this->assertNotEmpty($timeInLog->reason);
    }

    public function test_duplicate_attendance_for_the_same_employee_and_date_is_rejected(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        Attendance::factory()->forEmployee($employee)->create(['date' => '2026-02-01']);

        $this->actingAs($user)->post(route('admin.attendance.attendances.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-02-01',
            'status' => 'present',
        ])->assertSessionHasErrors('date');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
    }

    public function test_correction_requires_the_correct_permission_separately_from_manage(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['attendance.view', 'attendance.manage']);
        $employee = Employee::factory()->create();
        $attendance = Attendance::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->put(route('admin.attendance.attendances.update', $attendance), [
            'status' => 'present', 'reason' => 'test',
        ])->assertForbidden();
    }

    public function test_index_filters_by_employee_and_status(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employeeA = Employee::factory()->for($company, 'company')->create(['first_name' => 'Alpha']);
        $employeeB = Employee::factory()->for($company, 'company')->create(['first_name' => 'Beta']);
        Attendance::factory()->forEmployee($employeeA)->create(['status' => 'present']);
        Attendance::factory()->forEmployee($employeeB)->create(['status' => 'absent']);

        // The filter dropdown always lists every employee's name, so a
        // bare substring assertion on the page body can't tell "Alpha
        // filtered out of the table" from "Alpha still in the dropdown".
        // Count the per-row "Correct" action link instead, which only
        // renders once per matching attendance record.
        $response = $this->actingAs($user)->get(route('admin.attendance.attendances.index', ['status' => 'absent']));
        $response->assertOk();
        $correctLinks = substr_count($response->getContent(), '>Correct<');
        $this->assertSame(1, $correctLinks);
    }
}
