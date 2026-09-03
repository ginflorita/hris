<?php

namespace Tests\Feature\Security;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Blueprint §51 17.6/17.7/17.9/17.10/17.11 -- input security, file
 * upload, CSRF, XSS, SQL injection. Most of these are structural
 * verification rather than new tests:
 *
 * - SQL injection (17.11): a repo-wide grep for DB::raw/whereRaw/
 *   orderByRaw/selectRaw/havingRaw and any request-driven orderBy()
 *   found zero matches -- every query in this app goes through
 *   Eloquent's parameterized query builder, so there's no dynamic SQL
 *   identifier for a user to inject through in the first place. Nothing
 *   to allow-list because nothing builds a raw fragment from input.
 * - XSS (17.10): a repo-wide grep for Blade's raw-echo `{!! !!}` found
 *   exactly one use, in security/index.blade.php for a server-generated
 *   2FA QR code SVG -- not user input. Every other output in the app
 *   goes through `{{ }}`'s automatic htmlspecialchars() escaping. One
 *   concrete regression test below (a free-text Employee Note, standing
 *   in for blueprint's "Comments"/"Notes" examples) pins this down
 *   against a real field rather than trusting the grep alone.
 * - CSRF (17.9): Laravel's ValidateCsrfToken/VerifyCsrfToken ships in
 *   the `web` middleware group by default and bootstrap/app.php never
 *   excludes any route from it (confirmed by inspection, mirroring how
 *   17a confirmed no CSP-weakening exclusions existed). Laravel's own
 *   middleware short-circuits CSRF checks whenever
 *   app()->runningUnitTests() is true specifically so feature tests
 *   don't need to thread a token through every POST -- meaning a
 *   PHPUnit test asserting CSRF rejection would only prove something
 *   about the test environment, not this app, so none is written here.
 *   What *is* this app's own responsibility -- and was checked -- is
 *   that every `method="POST"` Blade form actually includes `@csrf`;
 *   a repo-wide per-file count comparison found zero forms without it.
 */
class InputSecurityTest extends TestCase
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

    public function test_a_note_containing_a_script_tag_is_rendered_escaped_not_executed(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();
        $payload = '<script>alert(document.cookie)</script>';

        $this->actingAs($user)->post(route('admin.employees.notes.store', $employee), [
            'note' => $payload,
        ])->assertRedirect();

        $response = $this->actingAs($user)->get(route('admin.employees.show', $employee));

        $response->assertDontSee($payload, false);
        $response->assertSee($payload);
    }

    public function test_employee_document_upload_rejects_a_disallowed_file_type(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.documents.store', $employee), [
            'document_type' => 'other',
            'title' => 'Malicious upload attempt',
            'file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, $employee->documents()->count());
    }

    public function test_employee_document_upload_rejects_an_oversized_file(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.documents.store', $employee), [
            'document_type' => 'other',
            'title' => 'Oversized file',
            'file' => UploadedFile::fake()->create('huge.pdf', 20000, 'application/pdf'),
        ])->assertSessionHasErrors('file');
    }

    public function test_employee_document_upload_accepts_an_allowed_file_type(): void
    {
        $user = $this->hrAdmin();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.documents.store', $employee), [
            'document_type' => 'other',
            'title' => 'Valid upload',
            'file' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(1, $employee->documents()->count());
    }
}
