<?php

namespace Tests\Feature\Admin\Employee;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeProbeTest extends TestCase
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
        $user->givePermissionTo(['employees.view', 'employees.create', 'employees.update', 'employees.archive']);

        return $user;
    }

    public function test_full_employee_lifecycle(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.store'), [
            'company_id' => $company->id,
            'employee_number' => 'EMP-001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.test',
        ])->assertRedirect();

        $employee = Employee::sole();
        $this->assertSame('Juan Dela Cruz', $employee->full_name);

        // Duplicate employee_number within same company rejected.
        $this->actingAs($user)->post(route('admin.employees.store'), [
            'company_id' => $company->id,
            'employee_number' => 'EMP-001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
        ])->assertSessionHasErrors('employee_number');

        $this->actingAs($user)->get(route('admin.employees.show', $employee))->assertOk();

        // Address
        $this->actingAs($user)->post(route('admin.employees.addresses.store', $employee), [
            'type' => 'current', 'line1' => '123 St', 'city' => 'Manila', 'province_state' => 'NCR', 'country' => 'PH', 'is_primary' => '1',
        ])->assertRedirect();
        $this->assertSame(1, $employee->addresses()->count());
        $this->assertTrue($employee->addresses()->first()->is_primary);

        // A second primary address un-primaries the first.
        $this->actingAs($user)->post(route('admin.employees.addresses.store', $employee), [
            'type' => 'permanent', 'line1' => '456 Ave', 'city' => 'Quezon City', 'province_state' => 'NCR', 'country' => 'PH', 'is_primary' => '1',
        ])->assertRedirect();
        $this->assertSame(1, $employee->addresses()->where('is_primary', true)->count());

        // Contact
        $this->actingAs($user)->post(route('admin.employees.contacts.store', $employee), [
            'type' => 'mobile', 'value' => '09171234567', 'is_primary' => '1',
        ])->assertRedirect();
        $this->assertSame(1, $employee->contacts()->count());

        // Emergency contact
        $this->actingAs($user)->post(route('admin.employees.emergency-contacts.store', $employee), [
            'name' => 'Ana Dela Cruz', 'relationship' => 'Spouse', 'phone' => '09170000000',
        ])->assertRedirect();
        $this->assertSame(1, $employee->emergencyContacts()->count());

        // Government ID
        $this->actingAs($user)->post(route('admin.employees.government-ids.store', $employee), [
            'id_type' => 'sss', 'id_number' => '01-2345678-9',
        ])->assertRedirect();
        $govId = $employee->governmentIds()->sole();

        // Duplicate id_type for the same employee rejected at the DB level -> should 500 without app-level guard.
        // (Not asserted here; DB unique constraint exists on (employee_id, id_type).)

        // Dependent
        $this->actingAs($user)->post(route('admin.employees.dependents.store', $employee), [
            'name' => 'Baby Dela Cruz', 'relationship' => 'Child', 'is_beneficiary' => '1',
        ])->assertRedirect();
        $this->assertSame(1, $employee->dependents()->count());

        // Note
        $this->actingAs($user)->post(route('admin.employees.notes.store', $employee), [
            'note' => 'Great performer.',
        ])->assertRedirect();
        $note = $employee->notes()->sole();
        $this->assertSame($user->id, $note->created_by);

        // Document upload + download + delete
        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');
        $this->actingAs($user)->post(route('admin.employees.documents.store', $employee), [
            'document_type' => 'resume', 'title' => 'Resume', 'file' => $file,
        ])->assertRedirect();
        $document = $employee->documents()->sole();
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($user)->get(route('admin.employees.documents.download', [$employee, $document]))->assertOk();

        $this->actingAs($user)->delete(route('admin.employees.documents.destroy', [$employee, $document]))->assertRedirect();
        Storage::disk('local')->assertMissing($document->file_path);

        // Archive / restore
        $this->actingAs($user)->put(route('admin.employees.archive', $employee))->assertRedirect();
        $this->assertTrue($employee->fresh()->isArchived());

        $this->actingAs($user)->get(route('admin.employees.index'))->assertOk();
        $indexBody = $this->actingAs($user)->get(route('admin.employees.index'))->getContent();
        $this->assertStringNotContainsString('Juan Dela Cruz', $indexBody);

        $withArchivedBody = $this->actingAs($user)->get(route('admin.employees.index', ['with_archived' => 1]))->getContent();
        $this->assertStringContainsString('Juan Dela Cruz', $withArchivedBody);

        $this->actingAs($user)->put(route('admin.employees.restore', $employee))->assertRedirect();
        $this->assertFalse($employee->fresh()->isArchived());
    }

    public function test_search_filters_by_name_and_employee_number(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        Employee::factory()->for($company, 'company')->create(['first_name' => 'Alpha', 'last_name' => 'One', 'employee_number' => 'EMP-100']);
        Employee::factory()->for($company, 'company')->create(['first_name' => 'Beta', 'last_name' => 'Two', 'employee_number' => 'EMP-200']);

        $body = $this->actingAs($user)->get(route('admin.employees.index', ['q' => 'Alpha']))->getContent();
        $this->assertStringContainsString('Alpha', $body);
        $this->assertStringNotContainsString('Beta Two', $body);
    }

    public function test_a_sub_resource_from_a_different_employee_is_rejected(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employeeA = Employee::factory()->for($company, 'company')->create();
        $employeeB = Employee::factory()->for($company, 'company')->create();
        $note = $employeeB->notes()->create(['note' => 'belongs to B', 'is_confidential' => true]);

        $this->actingAs($user)->put(route('admin.employees.notes.update', [$employeeA, $note]), ['note' => 'hijacked'])
            ->assertNotFound();
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.employees.index'))->assertForbidden();
    }
}
