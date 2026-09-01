<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentChangeType;
use App\Enums\EmploymentStatus;
use App\Enums\JobOfferStatus;
use App\Models\Application;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\JobOffer;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function recruiter(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['recruitment.view', 'recruitment.manage']);

        return $user;
    }

    private function recruiterWhoCanHire(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['recruitment.view', 'recruitment.manage', 'employees.create']);

        return $user;
    }

    public function test_extending_an_offer_creates_it_pending_and_moves_application_to_offered(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create(['status' => ApplicationStatus::FinalInterview]);

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.store', $application), [
            'employment_type' => 'regular',
            'offered_salary' => 45000,
            'start_date' => now()->addWeeks(3)->format('Y-m-d'),
        ])->assertRedirect();

        $offer = JobOffer::sole();
        $this->assertSame($application->id, $offer->application_id);
        $this->assertSame(JobOfferStatus::Pending, $offer->status);
        $this->assertSame($user->id, $offer->extended_by);
        $this->assertSame(ApplicationStatus::Offered, $application->refresh()->status);
    }

    public function test_cannot_extend_a_second_offer_while_one_is_pending(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create(['status' => ApplicationStatus::Offered]);
        JobOffer::factory()->forApplication($application)->create();

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.store', $application), [
            'employment_type' => 'regular',
            'offered_salary' => 45000,
            'start_date' => now()->addWeeks(3)->format('Y-m-d'),
        ])->assertStatus(422);

        $this->assertSame(1, JobOffer::count());
    }

    public function test_cannot_extend_an_offer_on_a_terminal_application(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create(['status' => ApplicationStatus::Rejected]);

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.store', $application), [
            'employment_type' => 'regular',
            'offered_salary' => 45000,
            'start_date' => now()->addWeeks(3)->format('Y-m-d'),
        ])->assertStatus(422);

        $this->assertSame(0, JobOffer::count());
    }

    public function test_accepting_requires_pending_and_can_only_happen_once(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $offer = JobOffer::factory()->forApplication($application)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.accept', [$application, $offer]))
            ->assertRedirect();
        $this->assertSame(JobOfferStatus::Accepted, $offer->refresh()->status);
        $this->assertNotNull($offer->responded_at);

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.accept', [$application, $offer]))
            ->assertStatus(422);
    }

    public function test_declining_requires_a_reason_and_records_it(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $offer = JobOffer::factory()->forApplication($application)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.decline', [$application, $offer]), [])
            ->assertSessionHasErrors('decision_reason');

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.decline', [$application, $offer]), [
            'decision_reason' => 'Accepted a competing offer.',
        ])->assertRedirect();

        $offer->refresh();
        $this->assertSame(JobOfferStatus::Declined, $offer->status);
        $this->assertSame('Accepted a competing offer.', $offer->decision_reason);
    }

    public function test_rescinding_requires_a_reason_and_only_applies_to_a_pending_offer(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $accepted = JobOffer::factory()->forApplication($application)->accepted()->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.rescind', [$application, $accepted]), [
            'decision_reason' => 'Budget frozen.',
        ])->assertStatus(422);

        $pending = JobOffer::factory()->forApplication($application)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.rescind', [$application, $pending]), [])
            ->assertSessionHasErrors('decision_reason');

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.rescind', [$application, $pending]), [
            'decision_reason' => 'Budget frozen.',
        ])->assertRedirect();
        $this->assertSame(JobOfferStatus::Rescinded, $pending->refresh()->status);
    }

    public function test_converting_requires_employees_create_permission_in_addition_to_recruitment_manage(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $offer = JobOffer::factory()->forApplication($application)->accepted()->create();

        $this->actingAs($user)->get(route('admin.recruitment.applications.offers.convert-form', [$application, $offer]))
            ->assertForbidden();

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.convert', [$application, $offer]), [
            'employee_number' => 'EMP-9000',
        ])->assertForbidden();

        $this->assertSame(0, Employee::count());
    }

    public function test_converting_an_accepted_offer_creates_an_employee_and_hire_employment_and_marks_application_hired(): void
    {
        $user = $this->recruiterWhoCanHire();
        $application = Application::factory()->create();
        $companyId = $application->jobPosting->company_id;
        $department = Department::factory()->create(['company_id' => $companyId]);
        $position = Position::factory()->create(['company_id' => $companyId]);
        $offer = JobOffer::factory()->forApplication($application)->accepted()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'offered_salary' => 55000,
        ]);

        $response = $this->actingAs($user)->post(route('admin.recruitment.applications.offers.convert', [$application, $offer]), [
            'employee_number' => 'EMP-9001',
        ]);

        $employee = Employee::sole();
        $response->assertRedirect(route('admin.employees.show', $employee));

        $this->assertSame($companyId, $employee->company_id);
        $this->assertSame('EMP-9001', $employee->employee_number);
        $this->assertSame($application->applicant->first_name, $employee->first_name);
        $this->assertSame($application->applicant->email, $employee->email);

        $employment = Employment::sole();
        $this->assertSame($employee->id, $employment->employee_id);
        $this->assertSame($department->id, $employment->department_id);
        $this->assertSame($position->id, $employment->position_id);
        $this->assertSame(EmploymentChangeType::Hire, $employment->change_type);
        $this->assertSame(EmploymentStatus::Active, $employment->status);
        $this->assertSame('55000.00', (string) $employment->basic_salary);
        $this->assertNull($employment->end_date);

        $offer->refresh();
        $this->assertSame($employee->id, $offer->converted_employee_id);
        $this->assertNotNull($offer->converted_at);
        $this->assertSame(ApplicationStatus::Hired, $application->refresh()->status);
    }

    public function test_cannot_convert_an_offer_that_is_not_accepted(): void
    {
        $user = $this->recruiterWhoCanHire();
        $application = Application::factory()->create();
        $offer = JobOffer::factory()->forApplication($application)->create();

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.convert', [$application, $offer]), [
            'employee_number' => 'EMP-9002',
        ])->assertStatus(422);

        $this->assertSame(0, Employee::count());
    }

    public function test_cannot_convert_the_same_offer_twice(): void
    {
        $user = $this->recruiterWhoCanHire();
        $application = Application::factory()->create();
        $offer = JobOffer::factory()->forApplication($application)->accepted()->create();

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.convert', [$application, $offer]), [
            'employee_number' => 'EMP-9003',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('admin.recruitment.applications.offers.convert', [$application, $offer]), [
            'employee_number' => 'EMP-9004',
        ])->assertStatus(422);

        $this->assertSame(1, Employee::count());
    }

    public function test_an_offer_from_another_application_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->recruiter();
        $applicationA = Application::factory()->create();
        $applicationB = Application::factory()->create();
        $offer = JobOffer::factory()->forApplication($applicationB)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.offers.accept', [$applicationA, $offer]))
            ->assertNotFound();
    }

    public function test_application_status_cannot_be_set_to_offered_or_hired_directly(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.status', $application), [
            'status' => 'offered',
        ])->assertSessionHasErrors('status');

        $this->actingAs($user)->put(route('admin.recruitment.applications.status', $application), [
            'status' => 'hired',
        ])->assertSessionHasErrors('status');

        $this->assertSame(ApplicationStatus::Applied, $application->refresh()->status);
    }
}
