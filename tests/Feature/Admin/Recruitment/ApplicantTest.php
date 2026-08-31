<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicantTest extends TestCase
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

    public function test_creating_an_applicant_with_a_resume_stores_it_privately(): void
    {
        Storage::fake('local');
        $user = $this->recruiter();

        $this->actingAs($user)->post(route('admin.recruitment.applicants.store'), [
            'first_name' => 'Jamie',
            'last_name' => 'Rivera',
            'email' => 'jamie.rivera@example.test',
            'source' => 'referral',
            'resume' => UploadedFile::fake()->create('resume.pdf', 200, 'application/pdf'),
        ])->assertRedirect();

        $applicant = Applicant::sole();
        $this->assertSame('Jamie Rivera', $applicant->full_name);
        $this->assertNotNull($applicant->resume_path);
        Storage::disk('local')->assertExists($applicant->resume_path);
    }

    public function test_downloading_a_resume_requires_recruitment_view(): void
    {
        Storage::fake('local');
        $user = $this->recruiter();
        $applicant = Applicant::factory()->create([
            'resume_path' => 'applicant-resumes/test.pdf',
            'resume_original_filename' => 'resume.pdf',
        ]);
        Storage::disk('local')->put($applicant->resume_path, 'fake-pdf-content');

        $this->actingAs($user)->get(route('admin.recruitment.applicants.resume', $applicant))->assertOk();

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('admin.recruitment.applicants.resume', $applicant))->assertForbidden();
    }

    public function test_search_filters_by_name_or_email(): void
    {
        $user = $this->recruiter();
        Applicant::factory()->create(['first_name' => 'Alpha', 'last_name' => 'One']);
        Applicant::factory()->create(['first_name' => 'Beta', 'last_name' => 'Two']);

        $response = $this->actingAs($user)->get(route('admin.recruitment.applicants.index', ['q' => 'Alpha']));

        $response->assertOk();
        $names = $response->viewData('applicants')->pluck('first_name')->all();
        $this->assertContains('Alpha', $names);
        $this->assertNotContains('Beta', $names);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.recruitment.applicants.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.recruitment.applicants.store'), [])->assertForbidden();
    }
}
