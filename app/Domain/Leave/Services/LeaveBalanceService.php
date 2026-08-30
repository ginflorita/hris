<?php

namespace App\Domain\Leave\Services;

use App\Enums\LeaveTransactionType;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

/**
 * The one place that writes to leave_balances — every caller (manual
 * adjustment, request approval, cancellation reversal) goes through
 * applyTransaction() so the balance is always backed by a
 * leave_transactions row explaining why it changed. Never update
 * LeaveBalance::$balance directly from a controller.
 */
class LeaveBalanceService
{
    public function applyTransaction(
        Employee $employee,
        LeaveType $leaveType,
        LeaveTransactionType $type,
        float $amount,
        string $date,
        ?string $reason = null,
        ?LeaveRequest $leaveRequest = null,
        ?int $createdBy = null,
    ): LeaveTransaction {
        return DB::transaction(function () use ($employee, $leaveType, $type, $amount, $date, $reason, $leaveRequest, $createdBy) {
            $balance = LeaveBalance::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id],
                    ['balance' => 0],
                );

            $newBalance = round((float) $balance->balance + $amount, 2);
            $balance->update(['balance' => $newBalance]);

            return LeaveTransaction::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'leave_request_id' => $leaveRequest?->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'date' => $date,
                'created_by' => $createdBy,
            ]);
        });
    }
}
