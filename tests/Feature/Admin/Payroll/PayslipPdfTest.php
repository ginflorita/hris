<?php

namespace Tests\Feature\Admin\Payroll;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function payrollAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view', 'payroll.export']);

        return $user;
    }

    public function test_payslip_pdf_downloads_once_finalized(): void
    {
        $user = $this->payrollAdmin();
        $item = PayrollItem::factory()->create();
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Finalized]);
        $item->lines()->create([
            'type' => 'earning', 'category' => 'basic_salary', 'label' => 'Basic Pay', 'amount' => $item->basic_salary,
        ]);

        $response = $this->actingAs($user)->get(route('admin.payroll.payroll-items.payslip', $item));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_payslip_pdf_is_blocked_before_finalization(): void
    {
        $user = $this->payrollAdmin();
        $item = PayrollItem::factory()->create();
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::ForReview]);

        $this->actingAs($user)->get(route('admin.payroll.payroll-items.payslip', $item))
            ->assertSessionHasErrors('payrollItem');
    }

    public function test_payslip_pdf_requires_payroll_export_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view']);
        $item = PayrollItem::factory()->create();
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Published]);

        $this->actingAs($user)->get(route('admin.payroll.payroll-items.payslip', $item))
            ->assertForbidden();
    }
}
