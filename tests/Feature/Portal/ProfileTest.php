<?php

namespace Tests\Feature\Portal;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Employment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_unlinked_account_sees_a_friendly_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('portal.profile.show'))
            ->assertOk()
            ->assertSee("isn't linked to an employee record", false);
    }

    public function test_employee_sees_their_own_profile_and_employment_history(): void
    {
        $user = $this->employeeUser();
        Employment::factory()->for($user->employee, 'employee')->for($user->employee->company, 'company')->create([
            'effective_date' => '2025-01-01',
            'end_date' => null,
        ]);

        $response = $this->actingAs($user)->get(route('portal.profile.show'));

        $response->assertOk()
            ->assertSee($user->employee->full_name)
            ->assertSee($user->employee->employee_number);
    }

    public function test_employee_can_download_their_own_document(): void
    {
        Storage::fake('local');
        $user = $this->employeeUser();
        $document = EmployeeDocument::factory()->for($user->employee, 'employee')->create([
            'file_path' => 'employee-documents/'.$user->employee_id.'/test.pdf',
        ]);
        Storage::disk('local')->put($document->file_path, 'fake pdf content');

        $this->actingAs($user)->get(route('portal.profile.documents.download', $document))
            ->assertOk();
    }

    public function test_employee_cannot_download_another_employees_document(): void
    {
        $user = $this->employeeUser();
        $otherEmployee = Employee::factory()->create();
        $document = EmployeeDocument::factory()->for($otherEmployee, 'employee')->create();

        $this->actingAs($user)->get(route('portal.profile.documents.download', $document))
            ->assertNotFound();
    }
}
