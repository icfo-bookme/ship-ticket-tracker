<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\Payment;
use App\Models\ShipTicketSale;
use App\Services\Finance\ExtraPaymentService;
use Illuminate\Http\Request;

class SalesReportService
{
    public function __construct(
        private readonly ExtraPaymentService $extraPaymentService,
    ) {}

    public function dataTable(Request $request): array
    {
        $filters = $this->filters($request);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $searchValue = (string) ($request->input('search.value') ?? '');
        $draw = $request->input('draw', 1);
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        $query = ShipTicketSale::with(['ships', 'companies', 'refunds.customerPayments', 'payments', 'bftn'])
            ->where('status', '!=', SaleStatus::Pending->value);

        $this->applyFilters($query, $filters);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $this->applySearch($query, $searchValue);
        }

        $filteredRecords = (clone $query)->count();
        $this->applyOrdering($query, $orderColumn, $orderDirection);

        $sales = $query->skip($start)->take($length)->get();
        $totals = $this->totals($filters, $searchValue);
        $refundOutflow = $totals->total_customer_refund_paid
            + $totals->total_extra_refunded_amount
            + $totals->total_partner_share_amount;

        return [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $sales->map(fn (ShipTicketSale $sale): array => $this->formatSale($sale)),
            'totals' => [
                'total_number_of_tickets' => $totals->total_number_of_tickets,
                'total_ticket_fee' => number_format($totals->total_ticket_fee, 2),
                'total_other_fee' => number_format($totals->total_other_fee, 2),
                'total_discount_amount' => number_format($totals->total_discount_amount, 2),
                'total_payable' => number_format($totals->total_payable, 2),
                'total_ticket_sales_after_discount' => number_format($totals->total_ticket_fee - $totals->total_discount_amount, 2),
                'total_received_amount' => number_format($totals->total_received_amount, 2),
                'total_extra_received_amount' => number_format($totals->total_extra_received_amount, 2),
                'total_extra_refunded_amount' => number_format($totals->total_extra_refunded_amount, 2),
                'total_refunded_tickets' => $totals->total_refunded_tickets,
                'total_due_amount' => number_format($totals->total_due_amount, 2),
                'total_completed_ticket_refund_amount' => number_format($totals->total_completed_ticket_refund_amount, 2),
                'total_gross_ticket_refund_amount' => number_format($totals->total_gross_ticket_refund_amount, 2),
                'total_refund_discount_amount' => number_format($totals->total_refund_discount_amount, 2),
                'total_customer_refund_paid' => number_format($totals->total_customer_refund_paid, 2),
                'total_due_adjusted_amount' => number_format($totals->total_due_adjusted_amount, 2),
                'total_partner_share_amount' => number_format($totals->total_partner_share_amount, 2),
                'total_company_retained_amount' => number_format($totals->total_company_retained_amount, 2),
                'total_bftn' => $totals->total_bftn,
                'total_bftn_pending' => $totals->total_bftn_pending,
                'total_bftn_received' => $totals->total_bftn_received,
                'total_bftn_amount' => number_format($totals->total_bftn_amount, 2),
                'total_bftn_pending_amount' => number_format($totals->total_bftn_pending_amount, 2),
                'total_bftn_received_amount' => number_format($totals->total_bftn_received_amount, 2),
                'report_refund_outflow' => number_format($refundOutflow, 2),
                'report_net_before_cashout' => number_format($totals->total_received_amount - $refundOutflow, 2),
                'net_sales_amount' => number_format(
                    $totals->total_ticket_fee
                        - $totals->total_discount_amount
                        - $totals->total_completed_ticket_refund_amount,
                    2
                ),
                'payment_method_totals' => $totals->payment_method_totals,
            ],
        ];
    }

    public function emptyResponse(Request $request): array
    {
        return [
            'draw' => $request->input('draw', 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'totals' => [
                'total_number_of_tickets' => 0,
                'total_ticket_fee' => '0.00',
                'total_other_fee' => '0.00',
                'total_discount_amount' => '0.00',
                'total_payable' => '0.00',
                'total_ticket_sales_after_discount' => '0.00',
                'total_received_amount' => '0.00',
                'total_extra_received_amount' => '0.00',
                'total_extra_refunded_amount' => '0.00',
                'total_refunded_tickets' => 0,
                'total_due_amount' => '0.00',
                'total_completed_ticket_refund_amount' => '0.00',
                'total_gross_ticket_refund_amount' => '0.00',
                'total_refund_discount_amount' => '0.00',
                'total_customer_refund_paid' => '0.00',
                'total_due_adjusted_amount' => '0.00',
                'total_partner_share_amount' => '0.00',
                'total_company_retained_amount' => '0.00',
                'total_bftn' => 0,
                'total_bftn_pending' => 0,
                'total_bftn_received' => 0,
                'total_bftn_amount' => '0.00',
                'total_bftn_pending_amount' => '0.00',
                'total_bftn_received_amount' => '0.00',
                'report_refund_outflow' => '0.00',
                'report_net_before_cashout' => '0.00',
                'net_sales_amount' => '0.00',
                'payment_method_totals' => [],
            ],
            'error' => 'An error occurred while generating the report.',
        ];
    }

    private function filters(Request $request): array
    {
        return [
            'ship_id' => $request->input('ship_id'),
            'company_id' => $request->input('company_id'),
            'journey_date' => $request->input('journey_date'),
            'return_date' => $request->input('return_date'),
            'payment_method' => $request->input('payment_method'),
            'bftn_status' => $request->input('bftn_status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'created_date' => $request->input('created_date'),
            'start_create_date' => $request->input('start_create_date'),
            'end_create_date' => $request->input('end_create_date'),
        ];
    }

    private function applyFilters($query, array $filters, string $prefix = ''): void
    {
        $column = fn (string $name): string => $prefix.$name;
        $saleIdColumn = $prefix === '' ? 'ship_ticket_sales.id' : $column('id');

        foreach (
            [
                'ship_id' => 'ship_id',
                'company_id' => 'company_id',
            ] as $filter => $columnName
        ) {
            if (! empty($filters[$filter])) {
                $query->where($column($columnName), $filters[$filter]);
            }
        }

        foreach (
            [
                'journey_date' => ['journey_date', '='],
                'return_date' => ['return_date', '='],
                'created_date' => ['created_at', '='],
                'start_date' => ['journey_date', '>='],
                'end_date' => ['journey_date', '<='],
                'start_create_date' => ['created_at', '>='],
                'end_create_date' => ['created_at', '<='],
            ] as $filter => [$columnName, $operator]
        ) {
            if (! empty($filters[$filter])) {
                $operator === '='
                    ? $query->whereDate($column($columnName), $filters[$filter])
                    : $query->whereDate($column($columnName), $operator, $filters[$filter]);
            }
        }

        if (! empty($filters['payment_method'])) {
            $paymentMethod = $filters['payment_method'];
            $query->where(function ($q) use ($paymentMethod): void {
                $q->whereHas('payments', fn ($sub) => $sub->where('payment_method', $paymentMethod));
            });
        }

        if ($filters['bftn_status'] === 'all') {
            $query->where($column('bftn_status'), 'yes');
        }

        if ($filters['bftn_status'] === 'received') {
            $query->where($column('bftn_status'), 'yes')
                ->whereExists(function ($sub) use ($saleIdColumn): void {
                    $sub->selectRaw('1')
                        ->from('bftn')
                        ->whereColumn('bftn.sales_id', $saleIdColumn)
                        ->where('bftn.received_status', 1);
                });
        }

        if ($filters['bftn_status'] === 'pending') {
            $query->where($column('bftn_status'), 'yes')
                ->whereNotExists(function ($sub) use ($saleIdColumn): void {
                    $sub->selectRaw('1')
                        ->from('bftn')
                        ->whereColumn('bftn.sales_id', $saleIdColumn)
                        ->where('bftn.received_status', 1);
                });
        }
    }

    private function applySearch($query, string $searchValue): void
    {
        $query->where(function ($q) use ($searchValue): void {
            $q->where('customer_name', 'like', "%{$searchValue}%")
                ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                ->orWhere('email', 'like', "%{$searchValue}%")
                ->orWhere('nid', 'like', "%{$searchValue}%")
                ->orWhere('sales_source', 'like', "%{$searchValue}%")
                ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                ->orWhereHas('payments', fn ($payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                ->orWhere('received_amount', 'like', "%{$searchValue}%")
                ->orWhere('due_amount', 'like', "%{$searchValue}%")
                ->orWhere('sold_by', 'like', "%{$searchValue}%")
                ->orWhereHas('categories.package', fn ($packages) => $packages->where('name', 'like', "%{$searchValue}%"))
                ->orWhere('status', 'like', "%{$searchValue}%")
                ->orWhereDate('journey_date', $searchValue)
                ->orWhereDate('return_date', $searchValue)
                ->orWhereDate('issued_date', $searchValue)
                ->orWhereDate('created_at', $searchValue)
                ->orWhere('id', $searchValue)
                ->orWhereHas('ships', fn ($shipQuery) => $shipQuery->where('name', 'like', "%{$searchValue}%"))
                ->orWhereHas('companies', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$searchValue}%"));
        });
    }

    private function applyOrdering($query, int $orderColumn, string $orderDirection): void
    {
        $orderableColumns = ['id', 'customer_name', 'customer_mobile', 'journey_date', 'number_of_ticket', 'received_amount', 'status', 'created_at'];
        $query->orderBy($orderableColumns[$orderColumn] ?? 'id', isset($orderableColumns[$orderColumn]) ? $orderDirection : 'desc');
    }

    private function totals(array $filters, ?string $searchValue = null): object
    {
        $searchValue ??= '';

        $query = ShipTicketSale::query()
            ->where('ship_ticket_sales.status', '!=', SaleStatus::Pending->value);

        $this->applyFilters($query, $filters, 'ship_ticket_sales.');

        if ($searchValue !== '') {
            $this->applySearch($query, $searchValue);
        }

        $paymentMethodFilters = $filters;
        unset($paymentMethodFilters['payment_method']);

        $paymentMethodSales = ShipTicketSale::query()
            ->where('ship_ticket_sales.status', '!=', SaleStatus::Pending->value);
        $this->applyFilters($paymentMethodSales, $paymentMethodFilters, 'ship_ticket_sales.');

        if ($searchValue !== '') {
            $this->applySearch($paymentMethodSales, $searchValue);
        }

        $paymentMethodTotals = Payment::query()
            ->whereIn('sales_id', $paymentMethodSales->select('ship_ticket_sales.id'))
            ->where(function ($query): void {
                $query->where('payment_method', '!=', 'Bank Transfer')
                    ->orWhereHas('sale.bftn', fn ($bftnQuery) => $bftnQuery->where('received_status', 1));
            })
            ->selectRaw('payment_method, COALESCE(SUM(received_amount), 0) AS amount')
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get()
            ->map(fn (Payment $payment): array => [
                'payment_method' => $payment->payment_method ?: 'Not specified',
                'amount' => round((float) $payment->amount, 2),
            ])
            ->values()
            ->all();

        $totals = $query->selectRaw('
            COALESCE(SUM(ship_ticket_sales.number_of_ticket), 0) AS total_number_of_tickets,
            COALESCE(SUM(ship_ticket_sales.ticket_fee), 0) AS total_ticket_fee,
            COALESCE(SUM(ship_ticket_sales.other_fee), 0) AS total_other_fee,
            COALESCE(SUM(ship_ticket_sales.discount_amount), 0) AS total_discount_amount,
            COALESCE(SUM(ship_ticket_sales.total_payable), 0) AS total_payable,
            COALESCE(SUM(ship_ticket_sales.received_amount), 0) AS total_received_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.received_amount > ship_ticket_sales.total_payable THEN ship_ticket_sales.received_amount - ship_ticket_sales.total_payable ELSE 0 END), 0) AS total_extra_received_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(refund_customer_payments.amount), 0) FROM refund_customer_payments INNER JOIN refunds ON refunds.id = refund_customer_payments.refund_id WHERE refunds.sales_id = ship_ticket_sales.id AND refunds.refund_type = "extra_payment" AND refund_customer_payments.status = "paid" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_extra_refunded_amount,
            COALESCE(SUM(ship_ticket_sales.due_amount), 0) AS total_due_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" THEN 1 ELSE 0 END), 0) AS total_bftn,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 0 THEN 1 ELSE 0 END), 0) AS total_bftn_pending,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 1 THEN 1 ELSE 0 END), 0) AS total_bftn_received,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS total_bftn_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 0 THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS total_bftn_pending_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 1 THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS total_bftn_received_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(refunded_number_of_tickets), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_refunded_tickets,
            COALESCE(SUM((SELECT COALESCE(SUM(COALESCE(NULLIF(gross_refund_amount, 0), refunded_amount, 0)), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_gross_ticket_refund_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(CASE
                WHEN COALESCE(NULLIF(refunds.gross_refund_amount, 0), refunds.refunded_amount, 0)
                    - COALESCE(refunds.refund_discount_amount, 0) > 0
                THEN COALESCE(NULLIF(refunds.gross_refund_amount, 0), refunds.refunded_amount, 0)
                    - COALESCE(refunds.refund_discount_amount, 0)
                ELSE 0
            END), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_completed_ticket_refund_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(refund_discount_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_refund_discount_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(due_adjusted_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_due_adjusted_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(partner_share_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_partner_share_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(company_retained_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_company_retained_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(refund_customer_payments.amount), 0) FROM refund_customer_payments INNER JOIN refunds ON refunds.id = refund_customer_payments.refund_id WHERE refunds.sales_id = ship_ticket_sales.id AND COALESCE(refunds.refund_type, "partial") != "extra_payment" AND refund_customer_payments.status = "paid" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_customer_refund_paid
        ')->first();

        $totals->payment_method_totals = $paymentMethodTotals;

        return $totals;
    }

    private function formatSale(ShipTicketSale $sale): array
    {
        $extraPaymentSummary = $this->extraPaymentService->summary($sale);
        $completedRefunds = $sale->refunds->filter(
            fn ($refund): bool => $refund->status === 'completed' || $refund->customer_refunded_at !== null
        );
        $ticketRefunds = $completedRefunds->where('refund_type', '!=', 'extra_payment');
        $extraRefunds = $completedRefunds->where('refund_type', 'extra_payment');
        $refundedTickets = (int) $ticketRefunds->sum('refunded_number_of_tickets');
        $grossRefundedAmount = (float) $ticketRefunds->sum(
            fn ($refund): float => (float) ($refund->gross_refund_amount ?? $refund->refunded_amount ?? 0)
        );
        $refundedAmount = (float) $ticketRefunds->sum(
            fn ($refund): float => max(
                (float) ($refund->gross_refund_amount ?? $refund->refunded_amount ?? 0)
                    - (float) $refund->refund_discount_amount,
                0,
            )
        );
        $customerRefundPaid = (float) $ticketRefunds->sum(fn ($refund): float => (float) $refund->customerPayments
            ->where('status', 'paid')
            ->sum('amount'));
        $extraRefundPaid = (float) $extraRefunds->sum(fn ($refund): float => (float) $refund->customerPayments
            ->where('status', 'paid')
            ->sum('amount'));
        $refundStatus = $refundedTickets >= $sale->number_of_ticket && $refundedTickets > 0
            ? 'Full Refund'
            : ($refundedTickets > 0 ? 'Partial Refund' : 'No Refund');

        return [
            'id' => $sale->id,
            'customer_name' => $sale->customer_name,
            'customer_mobile' => $sale->customer_mobile,
            'ship_name' => $sale->ships->name ?? 'N/A',
            'company_name' => $sale->companies->name ?? 'N/A',
            'journey_date' => $sale->journey_date,
            'return_date' => $sale->return_date,
            'number_of_ticket' => $sale->number_of_ticket,
            'ticket_fee' => $sale->ticket_fee,
            'received_amount' => $sale->received_amount,
            ...$extraPaymentSummary,
            'other_fee' => $sale->other_fee,
            'discount_amount' => $sale->discount_amount,
            'total_payable' => $sale->total_payable,
            'due_amount' => $sale->due_amount,
            'refunded_number_of_tickets' => $refundedTickets,
            'status' => $sale->status,
            'payments' => $sale->payments->sortBy('id')->map(fn ($payment): array => [
                'id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'amount' => (float) $payment->received_amount,
                'paid_date' => $payment->paid_date,
                'payment_datetime' => $payment->payment_datetime,
                'transaction_id' => $payment->transaction_id,
                'remark' => $payment->remark,
            ])->values()->all(),
            'created_at' => $sale->created_at,
            'refund_status' => $refundStatus,
            'refunded_tickets' => $refundedTickets,
            'refunded_amount' => $refundedAmount,
            'gross_refund_amount' => $grossRefundedAmount,
            'refund_discount_amount' => (float) $ticketRefunds->sum('refund_discount_amount'),
            'customer_charge_percent' => $ticketRefunds->last()?->customer_charge_percent,
            'partner_share_percent' => $ticketRefunds->last()?->partner_share_percent,
            'customer_refund_amount' => $customerRefundPaid,
            'customer_refund_paid' => $customerRefundPaid,
            'due_adjusted_amount' => (float) $ticketRefunds->sum('due_adjusted_amount'),
            'partner_share_amount' => (float) $ticketRefunds->sum('partner_share_amount'),
            'company_retained_amount' => (float) $ticketRefunds->sum('company_retained_amount'),
            'bftn_status' => $sale->bftn_status,
            'bftn_received' => $sale->bftn_status === 'yes' && (bool) $sale->bftn?->received_status,
            'bftn_received_at' => $sale->bftn?->received_at?->format('Y-m-d'),
            'bftn_amount' => $sale->bftn_status === 'yes' ? (float) $sale->received_amount : 0,
            'net_cash' => (float) $sale->received_amount
                - ($sale->bftn_status === 'yes' && ! (bool) $sale->bftn?->received_status ? (float) $sale->received_amount : 0)
                - $customerRefundPaid
                - $extraRefundPaid
                - (float) $ticketRefunds->sum('partner_share_amount'),
        ];
    }
}
