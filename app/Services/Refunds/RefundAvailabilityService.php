<?php

namespace App\Services\Refunds;

use App\Enums\RefundStatus;
use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Refund;
use App\Models\RefundTicket;
use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RefundAvailabilityService
{
    public function applyToQuery(Builder $query): void
    {
        $query->where('status', '!=', SaleStatus::Pending->value)
            ->whereDoesntHave('refunds', fn (Builder $refunds) => $refunds
                ->where('refund_type', '!=', 'extra_payment')
                ->whereNotIn('status', [RefundStatus::Cancelled->value, RefundStatus::Completed->value]))
            ->whereDoesntHave('refunds', fn (Builder $refunds) => $refunds
                ->where('refund_type', '!=', 'extra_payment')
                ->where('status', '!=', RefundStatus::Cancelled->value)
                ->where(function (Builder $invalid): void {
                    $invalid->where('refunded_number_of_tickets', '!=', RefundTicket::query()
                        ->selectRaw('COALESCE(SUM(refunded_quantity), 0)')
                        ->whereColumn('refund_id', 'refunds.id'))
                        ->orWhereHas('tickets', fn (Builder $tickets) => $tickets->whereNull('category_id')
                            ->orWhereNotIn('category_id', Category::query()->select('id')->whereColumn('ticket_id', 'refunds.sales_id')));
                }))
            ->whereHas('categories', fn (Builder $categories) => $categories
                ->where('quantity', '>', RefundTicket::query()
                    ->selectRaw('COALESCE(SUM(refunded_quantity), 0)')
                    ->whereColumn('category_id', 'categories.id')
                    ->whereIn('refund_id', Refund::query()->select('id')
                        ->whereColumn('sales_id', 'ship_ticket_sales.id')
                        ->where('status', '!=', RefundStatus::Cancelled->value)
                        ->where('refund_type', '!=', 'extra_payment'))));
    }

    /** @return Collection<int, Refund> */
    public function ticketRefunds(ShipTicketSale $sale, ?int $ignoredRefundId = null): Collection
    {
        $sale->loadMissing(['refunds.tickets', 'categories.package']);

        return $sale->refunds->filter(fn (Refund $refund): bool => $refund->status !== RefundStatus::Cancelled->value
            && $refund->refund_type !== 'extra_payment' && $refund->id !== $ignoredRefundId);
    }

    public function hasReliableBreakdown(ShipTicketSale $sale, ?int $ignoredRefundId = null): bool
    {
        return $this->ticketRefunds($sale, $ignoredRefundId)->every(fn (Refund $refund): bool => (int) $refund->refunded_number_of_tickets === (int) $refund->tickets->sum('refunded_quantity')
            && $refund->tickets->every(fn (RefundTicket $ticket): bool => $sale->categories->contains('id', $ticket->category_id)));
    }

    public function canCreate(ShipTicketSale $sale): bool
    {
        return ! $sale->trashed() && $sale->status !== SaleStatus::Pending->value
            && $this->hasReliableBreakdown($sale)
            && $this->ticketRefunds($sale)->every(fn (Refund $refund): bool => $refund->status === RefundStatus::Completed->value)
            && $this->categories($sale)->sum('remaining_quantity') > 0;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function categories(ShipTicketSale $sale, ?int $ignoredRefundId = null): Collection
    {
        $tickets = $this->ticketRefunds($sale, $ignoredRefundId)->flatMap->tickets;

        return $sale->categories->map(function (Category $category) use ($tickets): array {
            $reserved = (int) $tickets->where('category_id', $category->id)->sum('refunded_quantity');

            return [
                'id' => $category->id,
                'category_id' => $category->id,
                'type' => $category->type,
                'quantity' => $category->quantity,
                'purchased_quantity' => $category->quantity,
                'already_requested_quantity' => $reserved,
                'remaining_quantity' => max($category->quantity - $reserved, 0),
                'unit_price' => $category->unit_price,
                'package' => $category->package,
            ];
        })->values();
    }

    /** @return array{refund_discount_available: float, refund_gross_remaining: float} */
    public function balances(ShipTicketSale $sale, ?int $ignoredRefundId = null): array
    {
        $refunds = $this->ticketRefunds($sale, $ignoredRefundId);

        return [
            'refund_discount_available' => round(max(min((float) $sale->discount_amount, (float) $sale->ticket_value) - (float) $refunds->sum('refund_discount_amount'), 0), 2),
            'refund_gross_remaining' => round(max((float) $sale->ticket_value - (float) $refunds->sum('gross_refund_amount'), 0), 2),
        ];
    }
}
