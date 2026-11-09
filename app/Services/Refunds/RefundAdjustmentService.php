<?php

namespace App\Services\Refunds;

use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use App\Services\AuditLogService;
use App\Services\Sales\SaleFinancialService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class RefundAdjustmentService
{
    public function __construct(
        private readonly SaleFinancialService $financials,
        private readonly AuditLogService $auditLogs,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function search(array $filters): array
    {
        return Refund::query()
            ->with(['sale.payments', 'sale.categories', 'sale.refunds', 'adjustments', 'customerPayments'])
            ->whereIn('status', [RefundStatus::Requested->value, RefundStatus::PartnerApproved->value, RefundStatus::PaymentDetailsAdded->value])
            ->whereNull('customer_refunded_at')
            ->whereHas('sale', fn (Builder $sale): Builder => $sale->whereNull('deleted_at'))
            ->when($filters['refund_id'] ?? null, fn (Builder $query, int $id): Builder => $query->whereKey($id))
            ->when($filters['search'] ?? '', fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('id', $search)->orWhere('sales_id', $search)
                    ->orWhereHas('sale', fn (Builder $sale): Builder => $sale
                        ->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_mobile', 'like', "%{$search}%"));
            }))
            ->latest('id')->get()
            ->map(fn (Refund $refund): array => [
                'id' => $refund->id,
                'sales_id' => $refund->sales_id,
                'customer_name' => $refund->sale->customer_name,
                'customer_mobile' => $refund->sale->customer_mobile,
                'status' => $refund->status,
                'available_amount' => $this->availableAmount($refund),
                'can_adjust' => $refund->status !== RefundStatus::Requested->value,
            ])->filter(fn (array $refund): bool => $refund['available_amount'] > 0)->values()->all();
    }

    public function availableAmount(Refund $refund): float
    {
        $due = $refund->adjustment_started_at || $refund->customer_refunded_at || $refund->status === RefundStatus::Completed->value
            ? 0.0 : $this->financials->currentSummary($refund->sale)['due_amount'];

        return round(max((float) $refund->customer_refund_amount - $due
            - (float) $refund->adjustments->sum('received_amount')
            - (float) $refund->customerPayments->where('status', 'paid')->sum('amount'), 0), 2);
    }

    public function apply(ShipTicketSale $sale, int $refundId, float $amount, bool $limitToDue = false): void
    {
        $refund = Refund::query()->lockForUpdate()->findOrFail($refundId);
        $source = ShipTicketSale::query()->lockForUpdate()->find($refund->sales_id);
        if (! $source || $source->id === $sale->id || ! in_array($refund->status, [RefundStatus::PartnerApproved->value, RefundStatus::PaymentDetailsAdded->value], true)
            || $refund->customer_refunded_at !== null) {
            throw ValidationException::withMessages(['refund_id' => 'Select an approved, unpaid refund from an active sale.']);
        }
        $refund->setRelation('sale', $source);
        $amount = round($amount, 2);
        if ($limitToDue && $amount > $this->financials->currentSummary($sale)['due_amount']) {
            throw ValidationException::withMessages(['refund_adjustment_amount' => 'Adjustment cannot exceed the remaining sale due.']);
        }
        if ($amount <= 0 || $amount > $this->availableAmount($refund) || $amount > (float) $sale->total_payable) {
            throw ValidationException::withMessages(['refund_adjustment_amount' => 'Adjustment exceeds the available refund balance or the new sale payable.']);
        }
        $before = $this->auditLogs->snapshot($refund);
        if (! $refund->adjustment_started_at) {
            $dueAdjustment = min((float) $refund->customer_refund_amount, $this->financials->currentSummary($source)['due_amount']);
            $refund->update([
                'customer_refund_amount' => round((float) $refund->customer_refund_amount - $dueAdjustment, 2),
                'due_adjusted_amount' => $dueAdjustment,
                'adjustment_started_at' => now(),
            ]);
        }
        $sale->payments()->create([
            'refund_id' => $refund->id,
            'adjusted_by' => auth()->id(),
            'payment_method' => Payment::REFUND_ADJUSTED,
            'received_amount' => $amount,
            'settlement_status' => 'received',
            'is_bftn' => false,
            'paid_date' => now()->toDateString(),
            'payment_datetime' => now(),
            'remark' => "Adjusted from refund #{$refund->id}, sale #{$source->id}",
        ]);
        $refund->unsetRelation('adjustments');
        if ($this->availableAmount($refund) === 0.0) {
            $refund->update(['status' => RefundStatus::Completed->value]);
        }
        $this->auditLogs->record($refund, 'updated', $before, $this->auditLogs->snapshot($refund));
    }
}
