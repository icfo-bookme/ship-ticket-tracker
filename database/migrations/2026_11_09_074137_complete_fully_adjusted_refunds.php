<?php

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Services\AuditLogService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Refund::query()
            ->whereIn('status', [RefundStatus::PartnerApproved->value, RefundStatus::PaymentDetailsAdded->value])
            ->whereNotNull('adjustment_started_at')
            ->whereHas('adjustments')
            ->eachById(function (Refund $refund): void {
                app(AuditLogService::class)->capture($refund, function (Refund $locked): void {
                    if (in_array($locked->status, [RefundStatus::PartnerApproved->value, RefundStatus::PaymentDetailsAdded->value], true)
                        && $locked->settlementSummary()['remaining_refund_amount'] === 0.0) {
                        $locked->update(['status' => RefundStatus::Completed->value]);
                    }
                }, 'status_changed', system: true);
            });
    }

    /**
     * Completed financial settlements retain their status when rolling back.
     */
    public function down(): void {}
};
