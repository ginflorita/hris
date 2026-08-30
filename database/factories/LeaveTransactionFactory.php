<?php

namespace Database\Factories;

use App\Enums\LeaveTransactionType;
use App\Models\Employee;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveTransaction>
 */
class LeaveTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'type' => LeaveTransactionType::Accrual,
            'amount' => 1.25,
            'balance_after' => 1.25,
            'reason' => null,
            'date' => now()->toDateString(),
        ];
    }
}
