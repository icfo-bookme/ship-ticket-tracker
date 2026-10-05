<?php

namespace App\Services\Refunds;

use App\Enums\RefundStatus;
use App\Enums\SaleStatus;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use App\Services\Sales\SaleFinancialService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RefundListingService
{
    public function __construct(private readonly SaleFinancialService $saleFinancials) {}

    /** @return array<string, mixed> */
    public function refundableSales(Request $request): array
    {
        $query = ShipTicketSale::with(['ships', 'companies', 'payments', 'refunds', 'categories.package'])
            ->whereDoesntHave('refunds', fn (Builder $refunds) => $refunds->whereNotIn('status', [RefundStatus::Cancelled->value]))
            ->whereNotIn('status', [SaleStatus::Pending->value, SaleStatus::Refunded->value, SaleStatus::PartialRefunded->value]);

        $this->applySaleFilters($query, $request);
        $total = (clone $query)->count();
        $this->applySaleSearch($query, $request->input('search.value'));
        $filtered = (clone $query)->count();

        $sales = $query->orderBy('id')
            ->skip(max((int) $request->input('start', 0), 0))
            ->take($this->pageLength($request))
            ->get();

        $sales->each(function (ShipTicketSale $sale): void {
            $this->appendPaymentAndCategoryLabels($sale);
            $this->appendCurrentFinancials($sale);
        });

        return [
            'draw' => $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $sales,
        ];
    }

    /** @return array<string, mixed> */
    public function refundedSales(Request $request): array
    {
        $query = ShipTicketSale::with(['ships', 'companies', 'refunds', 'payments', 'categories.package'])
            ->where(fn (Builder $sales) => $sales
                ->whereIn('status', [SaleStatus::Refunded->value, SaleStatus::PartialRefunded->value])
                ->orWhereHas('refunds', fn (Builder $refunds) => $refunds
                    ->where('status', RefundStatus::Completed->value)
                    ->orWhereNotNull('customer_refunded_at')));

        $this->applySaleFilters($query, $request);
        $this->applySaleSearch($query, $request->input('search.value'));
        $total = (clone $query)->count();
        $refundTotals = $this->refundTotals((clone $query)->with('refunds')->get());

        $sales = $query->skip(max((int) $request->input('start', 0), 0))
            ->take($this->pageLength($request))
            ->get();

        $sales->each(function (ShipTicketSale $sale): void {
            $completedRefunds = $sale->refunds->filter($this->isCompletedRefund(...));
            $latestRefund = $completedRefunds->last();

            $sale->setRelation('refund', (new Refund)->forceFill([
                'id' => $latestRefund?->id,
                'refunded_number_of_tickets' => $completedRefunds->sum('refunded_number_of_tickets'),
                'refunded_amount' => $completedRefunds->sum('refunded_amount'),
                'gross_refund_amount' => $completedRefunds->sum('gross_refund_amount'),
                'refund_discount_amount' => $completedRefunds->sum('refund_discount_amount'),
                'customer_charge_percent' => $latestRefund?->customer_charge_percent,
                'partner_share_percent' => $latestRefund?->partner_share_percent,
                'customer_refund_amount' => $completedRefunds->sum('customer_refund_amount'),
                'customer_refund_before_discount' => $completedRefunds->sum('customer_refund_amount')
                    + $completedRefunds->sum('due_adjusted_amount')
                    + $completedRefunds->sum('refund_discount_amount'),
                'customer_refund_after_due_adjustment' => $completedRefunds->sum('customer_refund_amount'),
                'due_adjusted_amount' => $completedRefunds->sum('due_adjusted_amount'),
                'partner_share_amount' => $completedRefunds->sum('partner_share_amount'),
                'company_retained_amount' => $completedRefunds->sum('company_retained_amount'),
            ]));
            $this->appendPaymentAndCategoryLabels($sale);
        });

        return [
            'draw' => $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $sales,
            'total_refunded_tickets' => $refundTotals['tickets'],
            'total_refunded_amount' => $refundTotals['amount'],
            'total_gross_amount' => $refundTotals['gross_amount'],
            'total_customer_refund' => $refundTotals['customer_refund'],
            'total_partner_share' => $refundTotals['partner_share'],
            'total_company_retained' => $refundTotals['company_retained'],
        ];
    }

    public function refundedSale(int $saleId): ShipTicketSale
    {
        $sale = ShipTicketSale::with([
            'ships', 'companies', 'payments', 'refunds.tickets', 'refunds.customerPayments',
        ])->findOrFail($saleId);

        abort_unless($sale->refunds->contains(
            fn (Refund $refund): bool => $refund->status === RefundStatus::Completed->value
        ), 404);

        return $sale;
    }

    /** @return array{draw: mixed, recordsTotal: int, recordsFiltered: int, data: Collection<int, Refund>} */
    public function refundRequests(Request $request): array
    {
        $status = $request->input('status', RefundStatus::Requested->value);
        abort_unless(in_array($status, [
            RefundStatus::Requested->value,
            RefundStatus::PartnerApproved->value,
            RefundStatus::PaymentDetailsAdded->value,
            RefundStatus::Completed->value,
        ], true), 404);

        $query = Refund::with([
            'sale.ships', 'sale.companies', 'sale.categories.package', 'sale.payments', 'sale.refunds', 'tickets',
        ])
            ->whereNotNull('requested_at')
            ->when($status !== RefundStatus::Completed->value, fn (Builder $query) => $query->whereNull('customer_refunded_at'))
            ->where('status', $status)
            ->when($request->filled('journey_date'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale->whereDate('journey_date', $request->input('journey_date'))))
            ->when($request->filled('ship_id'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale->where('ship_id', $request->input('ship_id'))))
            ->when($request->filled('company_id'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale->where('company_id', $request->input('company_id'))));

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('sale', fn (Builder $sale) => $sale
                ->where('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_mobile', 'like', "%{$search}%")
                ->orWhere('id', $search));
        }

        $total = (clone $query)->count();
        $requests = $query->orderBy('id')
            ->skip(max((int) $request->input('start', 0), 0))
            ->take($this->pageLength($request))
            ->get();

        $requests->each(fn (Refund $refund) => $this->appendRefundRequestSummary($refund));

        return ['draw' => $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $requests];
    }

    private function appendRefundRequestSummary(Refund $refund): void
    {
        if ($refund->sale) {
            $this->appendCurrentFinancials($refund->sale);
        }

        $requestedQuantities = $refund->tickets->keyBy('category_id');
        $categories = $refund->sale?->categories ?? collect();
        $customerRefundAmount = (float) $refund->customer_refund_amount;
        $dueAdjustment = round(min(
            max((float) ($refund->sale?->due_amount ?? 0), 0),
            max($customerRefundAmount, 0),
        ), 2);

        $refund->setAttribute('total_purchase_tickets', (int) $categories->sum('quantity'));
        $refund->setAttribute('total_refund_tickets', (int) $refund->tickets->sum('refunded_quantity'));
        $refund->setAttribute('refund_calculation_valid', $customerRefundAmount >= 0);
        $refund->setAttribute('due_adjusted_amount', $dueAdjustment);
        $refund->setAttribute('customer_refund_after_due_adjustment', round(max($customerRefundAmount - $dueAdjustment, 0), 2));
        $refund->setAttribute('customer_refund_before_discount', round($customerRefundAmount + (float) $refund->refund_discount_amount, 2));
        $refund->setAttribute('edit_categories', $categories->map(fn ($category): array => [
            'id' => $category->id,
            'category_id' => $category->id,
            'type' => $category->type,
            'quantity' => $category->quantity,
            'purchased_quantity' => $category->quantity,
            'refunded_quantity' => $requestedQuantities->get($category->id)?->refunded_quantity ?? 0,
            'unit_amount' => $requestedQuantities->get($category->id)?->unit_amount,
            'package' => $category->package,
        ])->values());
    }

    /** @param Collection<int, ShipTicketSale> $sales
     * @return array{tickets: int, amount: float, gross_amount: float, customer_refund: float, partner_share: float, company_retained: float}
     */
    private function refundTotals(Collection $sales): array
    {
        return $sales->reduce(function (array $totals, ShipTicketSale $sale): array {
            $refunds = $sale->refunds->filter($this->isCompletedRefund(...));
            $totals['tickets'] += (int) $refunds->sum('refunded_number_of_tickets');
            $totals['amount'] += (float) $refunds->sum('refunded_amount');
            $totals['gross_amount'] += (float) $refunds->sum('gross_refund_amount');
            $totals['customer_refund'] += (float) $refunds->sum('customer_refund_amount');
            $totals['partner_share'] += (float) $refunds->sum('partner_share_amount');
            $totals['company_retained'] += (float) $refunds->sum('company_retained_amount');

            return $totals;
        }, ['tickets' => 0, 'amount' => 0.0, 'gross_amount' => 0.0, 'customer_refund' => 0.0, 'partner_share' => 0.0, 'company_retained' => 0.0]);
    }

    private function applySaleFilters(Builder $query, Request $request): void
    {
        $query->when($request->filled('ship_id'), fn (Builder $query) => $query->where('ship_id', $request->input('ship_id')))
            ->when($request->filled('company_id'), fn (Builder $query) => $query->where('company_id', $request->input('company_id')))
            ->when($request->filled('journey_date'), fn (Builder $query) => $query->whereDate('journey_date', $request->input('journey_date')));
    }

    private function applySaleSearch(Builder $query, mixed $searchValue): void
    {
        if (blank($searchValue)) {
            return;
        }

        $query->where(function (Builder $query) use ($searchValue): void {
            $query->where('customer_name', 'like', "%{$searchValue}%")
                ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                ->orWhere('email', 'like', "%{$searchValue}%")
                ->orWhere('nid', 'like', "%{$searchValue}%")
                ->orWhere('sales_source', 'like', "%{$searchValue}%")
                ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                ->orWhereHas('payments', fn (Builder $payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                ->orWhere('received_amount', 'like', "%{$searchValue}%")
                ->orWhere('due_amount', 'like', "%{$searchValue}%")
                ->orWhere('sold_by', 'like', "%{$searchValue}%")
                ->orWhereHas('categories.package', fn (Builder $packages) => $packages->where('name', 'like', "%{$searchValue}%"))
                ->orWhere('status', 'like', "%{$searchValue}%")
                ->orWhereDate('journey_date', $searchValue)
                ->orWhere('journey_date', 'like', "%{$searchValue}%")
                ->orWhereDate('return_date', $searchValue)
                ->orWhere('return_date', 'like', "%{$searchValue}%")
                ->orWhereDate('issued_date', $searchValue)
                ->orWhere('issued_date', 'like', "%{$searchValue}%")
                ->orWhereHas('ships', fn (Builder $ships) => $ships->where('name', 'like', "%{$searchValue}%"))
                ->orWhereHas('companies', fn (Builder $companies) => $companies->where('name', 'like', "%{$searchValue}%"))
                ->orWhere('id', $searchValue);
        });
    }

    private function appendPaymentAndCategoryLabels(ShipTicketSale $sale): void
    {
        $sale->setAttribute('payment_method', $sale->payments->first()->payment_method ?? null);
        $sale->setAttribute('ticket_category', $sale->categories
            ->map(fn ($category) => $category->package?->name)
            ->filter()
            ->unique()
            ->implode(', '));
    }

    private function appendCurrentFinancials(ShipTicketSale $sale): void
    {
        $summary = $this->saleFinancials->currentSummary($sale);

        $sale->setAttribute('received_amount', $summary['received_amount']);
        $sale->setAttribute('total_payable', $summary['total_payable']);
        $sale->setAttribute('due_amount', $summary['due_amount']);
        $sale->setAttribute('extra_received_amount', $summary['extra_received_amount']);
        $sale->setAttribute('extra_remaining_amount', $summary['extra_remaining_amount']);
    }

    private function pageLength(Request $request): int
    {
        return min(max((int) $request->input('length', 10), 1), 100);
    }

    private function isCompletedRefund(Refund $refund): bool
    {
        return $refund->status === RefundStatus::Completed->value || $refund->customer_refunded_at !== null;
    }
}
