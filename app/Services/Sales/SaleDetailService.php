<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\PrintedTicket;
use App\Models\ShipTicketSale;
use Illuminate\Support\Str;

class SaleDetailService
{
    public function __construct(private readonly SaleGroupingService $saleGrouping) {}

    /**
     * @return array{pdfFilenamePrefix: string, nextPdfNumber: int}
     */
    public function editContext(ShipTicketSale $sale): array
    {
        $this->appendExtraPaymentSummary($sale);

        return [
            'pdfFilenamePrefix' => $this->pdfFilenamePrefix($sale),
            'nextPdfNumber' => $sale->printedTickets()->count() + 1,
        ];
    }

    /**
     * @return array{number: int, groupByStatus: bool, groupById: int|null, groupingMessage: string|null, nextSale: ShipTicketSale|null, totalDepartureTickets: int, totalReturnTickets: int, ticketIssueViews: mixed, refundSummary: mixed, hasRefundActivity: bool, pdfFilenamePrefix: string}
     */
    public function ticketIssueContext(ShipTicketSale $sale, int $userId): array
    {
        $this->appendExtraPaymentSummary($sale);

        $viewedAt = now();
        $ticketIssueView = $sale->ticketIssueViews()->firstOrNew(['user_id' => $userId]);
        $ticketIssueView->first_viewed_at ??= $viewedAt;
        $ticketIssueView->last_viewed_at = $viewedAt;
        $ticketIssueView->save();

        $ticketIssueViews = $sale->ticketIssueViews()
            ->with('user:id,name')
            ->orderByDesc('last_viewed_at')
            ->get();

        $context = $this->printedTicketContext($sale);
        $totalDepartureTickets = (int) $sale->categories->where('type', 'departure')->sum('quantity');
        $totalReturnTickets = (int) $sale->categories->where('type', 'return')->sum('quantity');

        if ($sale->status === SaleStatus::PaymentVerified->value) {
            $sale->total_departure_tickets = $totalDepartureTickets;
            $sale->total_return_tickets = $totalReturnTickets;
        } else {
            $totalDepartureTickets = 0;
            $totalReturnTickets = 0;
        }

        $refundSummary = $sale->categories->map(function ($category) use ($sale): array {
            $completedQuantity = $sale->refunds
                ->where('status', 'completed')
                ->flatMap->tickets
                ->where('category_id', $category->id)
                ->sum('refunded_quantity');
            $pendingQuantity = $sale->refunds
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->flatMap->tickets
                ->where('category_id', $category->id)
                ->sum('refunded_quantity');
            $remainingQuantity = max((int) $category->quantity - (int) $completedQuantity, 0);

            return [
                'name' => $category->package?->name ?? 'Ticket category',
                'type' => ucfirst((string) $category->type),
                'purchased' => (int) $category->quantity,
                'refunded' => (int) $completedQuantity,
                'pending' => (int) $pendingQuantity,
                'remaining' => $remainingQuantity,
                'status' => $completedQuantity >= $category->quantity && $category->quantity > 0
                    ? 'Fully Refunded'
                    : ($completedQuantity > 0 ? 'Partially Refunded' : ($pendingQuantity > 0 ? 'Pending Refund' : 'No Refund')),
            ];
        });

        return [
            ...$context,
            'nextSale' => $this->nextSaleFor($sale),
            'totalDepartureTickets' => $totalDepartureTickets,
            'totalReturnTickets' => $totalReturnTickets,
            'ticketIssueViews' => $ticketIssueViews,
            'refundSummary' => $refundSummary,
            'hasRefundActivity' => $sale->refunds->contains(fn ($refund): bool => $refund->status !== 'cancelled'),
            'pdfFilenamePrefix' => $this->pdfFilenamePrefix($sale),
        ];
    }

    public function nextSaleFor(ShipTicketSale $sale): ?ShipTicketSale
    {
        return ShipTicketSale::query()
            ->where('status', $sale->status)
            ->where('id', '>', $sale->id)
            ->orderBy('id')
            ->first();
    }

    private function appendExtraPaymentSummary(ShipTicketSale $sale): void
    {
        $extraReceived = max((float) $sale->received_amount - (float) $sale->total_payable, 0);
        $extraRefunded = (float) $sale->refunds
            ->where('refund_type', 'extra_payment')
            ->filter(fn ($refund): bool => $refund->status === 'completed' || $refund->customer_refunded_at !== null)
            ->sum('customer_refund_amount');

        $sale->setAttribute('extra_received_amount', $extraReceived);
        $sale->setAttribute('extra_refunded_amount', $extraRefunded);
        $sale->setAttribute('extra_remaining_amount', max($extraReceived - $extraRefunded, 0));
    }

    /**
     * @return array{number: int, groupByStatus: bool, groupById: int|null, groupingMessage: string|null}
     */
    private function printedTicketContext(ShipTicketSale $sale): array
    {
        if (blank($sale->whatsapp) && blank($sale->whatsapp_username)) {
            return ['number' => 0, 'groupByStatus' => false, 'groupById' => null, 'groupingMessage' => null];
        }

        $contactField = filled($sale->whatsapp) ? 'whatsapp' : 'whatsapp_username';
        $contactValue = trim((string) $sale->{$contactField});
        $normalizedContact = $contactField === 'whatsapp_username' ? mb_strtolower($contactValue) : $contactValue;

        $matchingTickets = PrintedTicket::query()
            ->whereHas('sale', function ($query) use ($contactField, $normalizedContact): void {
                $query->whereRaw('LOWER(TRIM('.$contactField.')) = ?', [$normalizedContact]);
            })
            ->with('sale:id,status,collect_from_office,whatsapp,whatsapp_username')
            ->get()
            ->sortByDesc(fn (PrintedTicket $ticket): int => (int) Str::afterLast($ticket->filename, '-'));

        $noticeStatuses = [
            SaleStatus::TicketIssued->value,
            SaleStatus::TicketPrinted->value,
            SaleStatus::ShipmentIdEntered->value,
        ];
        $latestTicket = $matchingTickets->first();
        $latestNoticeTicket = $matchingTickets->first(
            fn (PrintedTicket $ticket): bool => in_array($ticket->sale?->status, $noticeStatuses, true)
        );
        $number = $latestNoticeTicket ? (int) Str::afterLast($latestNoticeTicket->filename, '-') : 0;

        if (! $latestTicket) {
            return ['number' => 0, 'groupByStatus' => false, 'groupById' => null, 'groupingMessage' => null];
        }

        $groupCandidates = $matchingTickets->reject(fn (PrintedTicket $ticket): bool => (int) $ticket->sales_id === (int) $sale->id);
        $groupIds = $groupCandidates
            ->map(fn (PrintedTicket $ticket): int => (int) ($ticket->group_by_id ?? $ticket->sales_id))
            ->unique();
        $groupMemberTickets = PrintedTicket::query()
            ->whereIn('group_by_id', $groupIds)
            ->get(['sales_id', 'group_by_id']);
        $groupSaleIds = $groupIds
            ->merge($groupCandidates->pluck('sales_id'))
            ->merge($groupMemberTickets->pluck('sales_id'))
            ->unique();
        $relatedSales = ShipTicketSale::query()
            ->whereIn('id', $groupSaleIds)
            ->get(['id', 'status', 'collect_from_office', 'whatsapp', 'whatsapp_username', 'address']);
        $eligibleTicket = $groupCandidates->first(function (PrintedTicket $ticket) use ($sale): bool {
            $groupById = (int) ($ticket->group_by_id ?? $ticket->sales_id);

            return $this->saleGrouping->canJoinGroup($sale, $groupById);
        });

        return [
            'number' => $number,
            'groupByStatus' => $eligibleTicket !== null,
            'groupById' => $eligibleTicket ? (int) ($eligibleTicket->group_by_id ?? $eligibleTicket->sales_id) : null,
            'groupingMessage' => $eligibleTicket ? null : $this->saleGrouping->incompatibleSalesMessage($sale, $relatedSales),
        ];
    }

    private function pdfFilenamePrefix(ShipTicketSale $sale): string
    {
        $contact = filled($sale->whatsapp) ? (string) $sale->whatsapp : (string) ($sale->whatsapp_username ?: 'whatsapp');
        $prefix = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($contact));

        return trim((string) $prefix, '-_') ?: 'sale-'.$sale->id;
    }
}
