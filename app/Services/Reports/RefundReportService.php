<?php

namespace App\Services\Reports;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\RefundCustomerPayment;
use App\Services\Sales\SaleFinancialService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RefundReportService
{
    public function __construct(private readonly SaleFinancialService $saleFinancials) {}

    /** @return array<string, mixed> */
    public function dataTable(Request $request): array
    {
        $query = $this->filteredQuery($request);
        $recordsTotal = (clone $query)->count();

        $this->applySearch($query, (string) $request->input('search.value', ''));
        $recordsFiltered = (clone $query)->count();
        $totals = $this->totals(clone $query);

        $refunds = $query->with([
            'sale.ships',
            'sale.companies',
            'sale.payments',
            'sale.refunds',
            'tickets',
            'customerPayments',
        ])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->skip(max((int) $request->input('start', 0), 0))
            ->take(min(max((int) $request->input('length', 10), 1), 100))
            ->get();

        return [
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $refunds->map(fn (Refund $refund): array => $this->formatRefund($refund)),
            'totals' => $totals,
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return Refund::query()
            ->whereNotNull('requested_at')
            ->when($request->filled('status') && $request->input('status') !== 'all', fn (Builder $query) => $query
                ->where('status', $request->input('status')))
            ->when($request->filled('refund_type') && $request->input('refund_type') !== 'all', fn (Builder $query) => $query
                ->where('refund_type', $request->input('refund_type')))
            ->when($request->filled('ship_id'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale
                ->where('ship_id', $request->input('ship_id'))))
            ->when($request->filled('company_id'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale
                ->where('company_id', $request->input('company_id'))))
            ->when($request->filled('start_date'), fn (Builder $query) => $query
                ->whereDate('requested_at', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn (Builder $query) => $query
                ->whereDate('requested_at', '<=', $request->input('end_date')));
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('refunds.id', 'like', "%{$search}%")
                ->orWhere('sales_id', 'like', "%{$search}%")
                ->orWhere('refund_type', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('refund_payment_details', 'like', "%{$search}%")
                ->orWhereHas('sale', fn (Builder $sale) => $sale
                    ->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_mobile', 'like', "%{$search}%"))
                ->orWhereHas('tickets', fn (Builder $tickets) => $tickets
                    ->where('category_name', 'like', "%{$search}%"));
        });
    }

    /** @return array<string, string|int> */
    private function totals(Builder $query): array
    {
        $activeQuery = (clone $query)->where('status', '!=', RefundStatus::Cancelled->value);
        $summary = (clone $query)->selectRaw('
            COUNT(*) AS request_count,
            SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) AS open_count,
            SUM(CASE WHEN status = ? OR customer_refunded_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_count,
            COALESCE(SUM(CASE WHEN status != ? THEN gross_refund_amount ELSE 0 END), 0) AS gross_amount,
            COALESCE(SUM(CASE WHEN status != ? THEN refund_discount_amount ELSE 0 END), 0) AS discount_amount,
            COALESCE(SUM(CASE WHEN status != ? THEN other_fee_deduction ELSE 0 END), 0) AS other_fee_deduction,
            COALESCE(SUM(CASE WHEN status = ? OR customer_refunded_at IS NOT NULL THEN due_adjusted_amount ELSE 0 END), 0) AS due_adjusted_amount,
            COALESCE(SUM(CASE WHEN status != ? THEN partner_share_amount ELSE 0 END), 0) AS partner_share_amount
        ', [
            RefundStatus::Requested->value,
            RefundStatus::PartnerApproved->value,
            RefundStatus::PaymentDetailsAdded->value,
            RefundStatus::Completed->value,
            RefundStatus::Cancelled->value,
            RefundStatus::Cancelled->value,
            RefundStatus::Cancelled->value,
            RefundStatus::Completed->value,
            RefundStatus::Cancelled->value,
        ])->first();

        $paidCustomerRefund = RefundCustomerPayment::query()
            ->where('status', 'paid')
            ->whereIn('refund_id', (clone $activeQuery)->select('refunds.id'))
            ->sum('amount');
        $extraPaymentRefundPaid = $this->extraPaymentRefundPaid(clone $activeQuery);

        return [
            'request_count' => (int) $summary->request_count,
            'open_count' => (int) $summary->open_count,
            'completed_count' => (int) $summary->completed_count,
            'gross_amount' => number_format((float) $summary->gross_amount, 2, '.', ''),
            'discount_amount' => number_format((float) $summary->discount_amount, 2, '.', ''),
            'other_fee_deduction' => number_format((float) $summary->other_fee_deduction, 2, '.', ''),
            'due_adjusted_amount' => number_format((float) $summary->due_adjusted_amount, 2, '.', ''),
            'partner_share_amount' => number_format((float) $summary->partner_share_amount, 2, '.', ''),
            'customer_refund_paid' => number_format((float) $paidCustomerRefund, 2, '.', ''),
            'extra_payment_refund_paid' => number_format($extraPaymentRefundPaid, 2, '.', ''),
        ];
    }

    private function extraPaymentRefundPaid(Builder $activeQuery): float
    {
        $extraRefunds = (clone $activeQuery)->where('refund_type', 'extra_payment');
        $paidPayments = RefundCustomerPayment::query()
            ->where('status', 'paid')
            ->whereIn('refund_id', (clone $extraRefunds)->select('refunds.id'));
        $paidAmount = (float) (clone $paidPayments)->sum('amount');
        $settledWithoutPayment = Refund::query()
            ->whereIn('id', (clone $extraRefunds)->select('refunds.id'))
            ->where(function (Builder $query): void {
                $query->where('status', RefundStatus::Completed->value)
                    ->orWhereNotNull('customer_refunded_at');
            })
            ->whereDoesntHave('customerPayments', fn (Builder $payments) => $payments->where('status', 'paid'))
            ->sum('customer_refund_amount');

        return $paidAmount + (float) $settledWithoutPayment;
    }

    /** @return array<string, mixed> */
    private function formatRefund(Refund $refund): array
    {
        $sale = $refund->sale;
        $isCompleted = $refund->status === RefundStatus::Completed->value || $refund->customer_refunded_at !== null;
        $isCancelled = $refund->status === RefundStatus::Cancelled->value;
        $customerRefundAmount = (float) $refund->customer_refund_amount
            + ($isCompleted ? (float) $refund->due_adjusted_amount : 0);
        $currentDue = $sale ? $this->saleFinancials->currentSummary($sale)['due_amount'] : 0;
        $dueAdjustedAmount = $isCompleted
            ? (float) $refund->due_adjusted_amount
            : ($isCancelled ? 0 : min(max($currentDue, 0), max($customerRefundAmount, 0)));
        $finalCustomerRefund = $isCompleted
            ? (float) $refund->customer_refund_amount
            : ($isCancelled ? 0 : max($customerRefundAmount - $dueAdjustedAmount, 0));

        $customerRefundPaid = (float) $refund->customerPayments
            ->where('status', 'paid')
            ->sum('amount');

        if (
            $refund->refund_type === 'extra_payment'
            && $customerRefundPaid === 0.0
            && $isCompleted
        ) {
            $customerRefundPaid = (float) $refund->customer_refund_amount;
        }

        return [
            'id' => $refund->id,
            'sales_id' => $refund->sales_id,
            'customer_name' => $sale?->customer_name ?? 'N/A',
            'customer_mobile' => $sale?->customer_mobile ?? 'N/A',
            'ship_name' => $sale?->ships?->name ?? 'N/A',
            'company_name' => $sale?->companies?->name ?? 'N/A',
            'journey_date' => $sale?->journey_date,
            'requested_at' => $refund->requested_at?->toDateTimeString(),
            'refund_type' => $refund->refund_type,
            'status' => $refund->status,
            'refunded_number_of_tickets' => (int) $refund->refunded_number_of_tickets,
            'gross_refund_amount' => number_format((float) ($refund->gross_refund_amount ?? $refund->refunded_amount), 2, '.', ''),
            'refund_discount_amount' => number_format((float) $refund->refund_discount_amount, 2, '.', ''),
            'other_fee_deduction' => number_format((float) $refund->other_fee_deduction, 2, '.', ''),
            'customer_charge_amount' => number_format((float) $refund->customer_charge_amount, 2, '.', ''),
            'partner_share_amount' => number_format((float) $refund->partner_share_amount, 2, '.', ''),
            'company_retained_amount' => number_format((float) $refund->company_retained_amount, 2, '.', ''),
            'due_adjusted_amount' => number_format($dueAdjustedAmount, 2, '.', ''),
            'customer_refund_amount' => number_format($customerRefundAmount, 2, '.', ''),
            'final_customer_refund' => number_format($finalCustomerRefund, 2, '.', ''),
            'customer_refund_paid' => number_format($customerRefundPaid, 2, '.', ''),
            'refund_payment_details' => $refund->refund_payment_details,
            'remark' => $refund->remark,
            'tickets' => $refund->tickets->map(fn ($ticket): array => [
                'category_name' => $ticket->category_name,
                'category_type' => $ticket->category_type,
                'purchased_quantity' => $ticket->purchased_quantity,
                'refunded_quantity' => $ticket->refunded_quantity,
                'unit_amount' => number_format((float) $ticket->unit_amount, 2, '.', ''),
                'gross_amount' => number_format((float) $ticket->gross_amount, 2, '.', ''),
            ])->values(),
            'customer_payments' => $refund->customerPayments->map(fn ($payment): array => [
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
                'status' => $payment->status,
            ])->values(),
        ];
    }
}
