<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundCustomerPayment;
use App\Models\ShipTicketSale;
use App\Models\VerifyTracker;
use App\Services\Finance\ExtraPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $requestedDirection = strtolower((string) $request->input('order.0.dir', 'asc'));
        $orderDirection = in_array($requestedDirection, ['asc', 'desc'], true)
            ? $requestedDirection
            : 'asc';

        $query = ShipTicketSale::query()
            ->with([
                'ships',
                'companies',
                'refunds.customerPayments',
                'payments',
                'categories',
                'verifyby.verifiedByUser:id,name',
            ])
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
                'total_ticket_value' => number_format($totals->total_ticket_value, 2),
                'total_other_fee' => number_format($totals->total_other_fee, 2),
                'total_discount_amount' => number_format($totals->total_discount_amount, 2),
                'total_payable' => number_format($totals->total_payable, 2),
                'total_ticket_sales_after_discount' => number_format($totals->total_ticket_value - $totals->total_discount_amount, 2),
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
                    $totals->total_ticket_value
                        - $totals->total_discount_amount
                        - $totals->total_completed_ticket_refund_amount,
                    2
                ),
                'average_payment_verification_seconds' => $totals->average_payment_verification_seconds !== null
                    ? round((float) $totals->average_payment_verification_seconds)
                    : null,
                'payment_verification_sample_size' => (int) $totals->payment_verification_sample_size,
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
                'total_ticket_value' => '0.00',
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
                'average_payment_verification_seconds' => null,
                'payment_verification_sample_size' => 0,
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
            $query->whereHas('payments', fn ($payments) => $payments->where('is_bftn', true));
        }

        if ($filters['bftn_status'] === 'received') {
            $query->whereHas('payments', fn ($payments) => $payments
                ->where('is_bftn', true)
                ->where('settlement_status', 'received'));
        }

        if ($filters['bftn_status'] === 'pending') {
            $query->whereHas('payments', fn ($payments) => $payments
                ->where('is_bftn', true)
                ->where('settlement_status', 'pending'));
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
                ->orWhereRaw('(
                    SELECT COALESCE(SUM(categories.quantity * categories.unit_price), 0)
                    FROM categories
                    WHERE categories.ticket_id = ship_ticket_sales.id
                ) LIKE ?', ["%{$searchValue}%"])
                ->orWhereHas('payments', fn ($payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                ->orWhereRaw('(
                    SELECT COALESCE(SUM(categories.quantity), 0)
                    FROM categories
                    WHERE categories.ticket_id = ship_ticket_sales.id
                ) LIKE ?', ["%{$searchValue}%"])
                ->orWhereHas('payments', fn ($payments) => $payments->where('received_amount', 'like', "%{$searchValue}%"))
                ->orWhereRaw('('.ShipTicketSale::dueAmountSql().') LIKE ?', ["%{$searchValue}%"])
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
        $orderableColumns = ['id', 'customer_name', 'customer_mobile', 'journey_date', 'ticket_count', 'status', 'created_at'];

        if ($orderColumn === 4) {
            $query->orderByRaw('(
                SELECT COALESCE(SUM(categories.quantity), 0)
                FROM categories
                WHERE categories.ticket_id = ship_ticket_sales.id
            ) '.$orderDirection);

            return;
        }

        if ($orderColumn === 5) {
            $query->orderByRaw('(
                SELECT COALESCE(SUM(CASE WHEN payments.is_bftn = 0 OR payments.settlement_status = "received" THEN payments.received_amount ELSE 0 END), 0)
                FROM payments
                WHERE payments.sales_id = ship_ticket_sales.id
            ) '.$orderDirection);

            return;
        }

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
                $query->where(function ($payments): void {
                    $payments->where('is_bftn', false);
                })->orWhere(function ($payments): void {
                    $payments->where('is_bftn', true)
                        ->where('settlement_status', 'received');
                });
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

        $firstPaymentVerifications = VerifyTracker::query()
            ->select('ticket_id')
            ->selectRaw('MIN(created_at) AS first_verified_at')
            ->where('name', SaleStatus::PaymentVerified->value)
            ->groupBy('ticket_id');

        $verificationSales = ShipTicketSale::query()
            ->where('ship_ticket_sales.status', '!=', SaleStatus::Pending->value);
        $this->applyFilters($verificationSales, $filters, 'ship_ticket_sales.');

        if ($searchValue !== '') {
            $this->applySearch($verificationSales, $searchValue);
        }

        $elapsedSecondsExpression = DB::connection()->getDriverName() === 'sqlite'
            ? '(julianday(first_payment_verifications.first_verified_at) - julianday(ship_ticket_sales.created_at)) * 86400'
            : 'TIMESTAMPDIFF(SECOND, ship_ticket_sales.created_at, first_payment_verifications.first_verified_at)';

        $verificationStats = $verificationSales
            ->joinSub(
                $firstPaymentVerifications,
                'first_payment_verifications',
                'first_payment_verifications.ticket_id',
                '=',
                'ship_ticket_sales.id'
            )
            ->whereRaw('first_payment_verifications.first_verified_at >= ship_ticket_sales.created_at')
            ->selectRaw("AVG({$elapsedSecondsExpression}) AS average_payment_verification_seconds")
            ->selectRaw('COUNT(*) AS payment_verification_sample_size')
            ->first();

        $categoryTotals = Category::query()
            ->selectRaw('ticket_id, SUM(quantity) AS ticket_count, SUM(quantity * unit_price) AS ticket_value')
            ->groupBy('ticket_id');

        $paymentTotals = Payment::query()
            ->selectRaw(<<<'SQL'
                sales_id,
                SUM(CASE WHEN is_bftn = 0 OR settlement_status = "received" THEN received_amount ELSE 0 END) AS settled_amount,
                SUM(CASE WHEN is_bftn = 1 THEN 1 ELSE 0 END) AS bftn_count,
                SUM(CASE WHEN is_bftn = 1 AND settlement_status = "pending" THEN 1 ELSE 0 END) AS bftn_pending_count,
                SUM(CASE WHEN is_bftn = 1 AND settlement_status = "received" THEN 1 ELSE 0 END) AS bftn_received_count,
                SUM(CASE WHEN is_bftn = 1 THEN received_amount ELSE 0 END) AS bftn_amount,
                SUM(CASE WHEN is_bftn = 1 AND settlement_status = "pending" THEN received_amount ELSE 0 END) AS bftn_pending_amount,
                SUM(CASE WHEN is_bftn = 1 AND settlement_status = "received" THEN received_amount ELSE 0 END) AS bftn_received_amount
                SQL)
            ->groupBy('sales_id');

        $completedRefunds = Refund::query()
            ->where(fn ($refunds) => $refunds
                ->where('refund_type', '!=', 'extra_payment')
                ->orWhereNull('refund_type'))
            ->where(fn ($refunds) => $refunds
                ->where('status', 'completed')
                ->orWhereNotNull('customer_refunded_at'));

        $refundTotals = (clone $completedRefunds)
            ->selectRaw(<<<'SQL'
                sales_id,
                SUM(refunded_number_of_tickets) AS refunded_tickets,
                SUM(gross_refund_amount) AS gross_ticket_refund_amount,
                SUM(CASE WHEN gross_refund_amount - refund_discount_amount > 0 THEN gross_refund_amount - refund_discount_amount ELSE 0 END) AS completed_ticket_refund_amount,
                SUM(refund_discount_amount) AS refund_discount_amount,
                SUM(due_adjusted_amount) AS due_adjusted_amount,
                SUM(partner_share_amount) AS partner_share_amount,
                SUM(company_retained_amount) AS company_retained_amount
                SQL)
            ->groupBy('sales_id');

        $customerRefundTotals = RefundCustomerPayment::query()
            ->join('refunds', 'refunds.id', '=', 'refund_customer_payments.refund_id')
            ->where('refund_customer_payments.status', 'paid')
            ->where(fn ($refunds) => $refunds
                ->where('refunds.refund_type', '!=', 'extra_payment')
                ->orWhereNull('refunds.refund_type'))
            ->where(fn ($refunds) => $refunds
                ->where('refunds.status', 'completed')
                ->orWhereNotNull('refunds.customer_refunded_at'))
            ->selectRaw('refunds.sales_id, SUM(refund_customer_payments.amount) AS customer_refund_paid')
            ->groupBy('refunds.sales_id');

        $extraRefundTotals = RefundCustomerPayment::query()
            ->join('refunds', 'refunds.id', '=', 'refund_customer_payments.refund_id')
            ->where('refund_customer_payments.status', 'paid')
            ->where('refunds.refund_type', 'extra_payment')
            ->where(fn ($refunds) => $refunds
                ->where('refunds.status', 'completed')
                ->orWhereNotNull('refunds.customer_refunded_at'))
            ->selectRaw('refunds.sales_id, SUM(refund_customer_payments.amount) AS extra_refund_paid')
            ->groupBy('refunds.sales_id');

        $totals = $query
            ->leftJoinSub($categoryTotals, 'category_totals', 'category_totals.ticket_id', '=', 'ship_ticket_sales.id')
            ->leftJoinSub($paymentTotals, 'payment_totals', 'payment_totals.sales_id', '=', 'ship_ticket_sales.id')
            ->leftJoinSub($refundTotals, 'refund_totals', 'refund_totals.sales_id', '=', 'ship_ticket_sales.id')
            ->leftJoinSub($customerRefundTotals, 'customer_refund_totals', 'customer_refund_totals.sales_id', '=', 'ship_ticket_sales.id')
            ->leftJoinSub($extraRefundTotals, 'extra_refund_totals', 'extra_refund_totals.sales_id', '=', 'ship_ticket_sales.id')
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(category_totals.ticket_count), 0) AS total_number_of_tickets,
                COALESCE(SUM(category_totals.ticket_value), 0) AS total_ticket_value,
                COALESCE(SUM(ship_ticket_sales.other_fee), 0) AS total_other_fee,
                COALESCE(SUM(ship_ticket_sales.discount_amount), 0) AS total_discount_amount,
                COALESCE(SUM(ship_ticket_sales.total_payable), 0) AS total_payable,
                COALESCE(SUM(payment_totals.settled_amount), 0) AS total_received_amount,
                COALESCE(SUM(CASE WHEN payment_totals.settled_amount > ship_ticket_sales.total_payable THEN payment_totals.settled_amount - ship_ticket_sales.total_payable ELSE 0 END), 0) AS total_extra_received_amount,
                COALESCE(SUM(extra_refund_totals.extra_refund_paid), 0) AS total_extra_refunded_amount,
                COALESCE(SUM(CASE WHEN ship_ticket_sales.total_payable - COALESCE(payment_totals.settled_amount, 0) - COALESCE(payment_totals.bftn_pending_amount, 0) - COALESCE(refund_totals.due_adjusted_amount, 0) > 0 THEN ship_ticket_sales.total_payable - COALESCE(payment_totals.settled_amount, 0) - COALESCE(payment_totals.bftn_pending_amount, 0) - COALESCE(refund_totals.due_adjusted_amount, 0) ELSE 0 END), 0) AS total_due_amount,
                COALESCE(SUM(payment_totals.bftn_count), 0) AS total_bftn,
                COALESCE(SUM(payment_totals.bftn_pending_count), 0) AS total_bftn_pending,
                COALESCE(SUM(payment_totals.bftn_received_count), 0) AS total_bftn_received,
                COALESCE(SUM(payment_totals.bftn_amount), 0) AS total_bftn_amount,
                COALESCE(SUM(payment_totals.bftn_pending_amount), 0) AS total_bftn_pending_amount,
                COALESCE(SUM(payment_totals.bftn_received_amount), 0) AS total_bftn_received_amount,
                COALESCE(SUM(refund_totals.refunded_tickets), 0) AS total_refunded_tickets,
                COALESCE(SUM(refund_totals.gross_ticket_refund_amount), 0) AS total_gross_ticket_refund_amount,
                COALESCE(SUM(refund_totals.completed_ticket_refund_amount), 0) AS total_completed_ticket_refund_amount,
                COALESCE(SUM(refund_totals.refund_discount_amount), 0) AS total_refund_discount_amount,
                COALESCE(SUM(refund_totals.due_adjusted_amount), 0) AS total_due_adjusted_amount,
                COALESCE(SUM(refund_totals.partner_share_amount), 0) AS total_partner_share_amount,
                COALESCE(SUM(refund_totals.company_retained_amount), 0) AS total_company_retained_amount,
                COALESCE(SUM(customer_refund_totals.customer_refund_paid), 0) AS total_customer_refund_paid
                SQL)->first();

        $totals->payment_method_totals = $paymentMethodTotals;
        $totals->average_payment_verification_seconds = $verificationStats->average_payment_verification_seconds;
        $totals->payment_verification_sample_size = $verificationStats->payment_verification_sample_size;

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
            fn ($refund): float => (float) $refund->gross_refund_amount
        );
        $refundedAmount = (float) $ticketRefunds->sum(
            fn ($refund): float => max(
                (float) $refund->gross_refund_amount
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
        $refundStatus = $refundedTickets >= $sale->ticket_count && $refundedTickets > 0
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
            'ticket_count' => $sale->ticket_count,
            'ticket_value' => $sale->ticket_value,
            'received_amount' => $this->settledAmount($sale),
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
                'is_bftn' => (bool) $payment->is_bftn,
                'settlement_status' => $payment->settlement_status,
                'bftn_issue_datetime' => $payment->bftn_issue_datetime?->toDateTimeString(),
                'received_at' => $payment->received_at?->toDateTimeString(),
                'paid_date' => $payment->paid_date,
                'payment_datetime' => $payment->payment_datetime,
                'transaction_id' => $payment->transaction_id,
                'remark' => $payment->remark,
            ])->values()->all(),
            'created_at' => $sale->created_at,
            'verification_timeline' => $sale->verifyby
                ->sortBy(fn ($verification) => [$verification->created_at?->timestamp ?? 0, $verification->id])
                ->map(fn ($verification): array => [
                    'status' => $verification->name,
                    'verified_by' => $verification->verifiedByUser?->name,
                    'verified_at' => $verification->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
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
            'bftn_status' => $sale->payments->contains(fn ($payment): bool => $payment->is_bftn) ? 'yes' : 'no',
            'bftn_received' => $sale->payments->where('is_bftn', true)->isNotEmpty()
                && $sale->payments->where('is_bftn', true)->every(fn ($payment): bool => $payment->settlement_status === 'received'),
            'bftn_received_at' => $sale->payments->where('is_bftn', true)->max('received_at')?->format('Y-m-d'),
            'bftn_amount' => (float) $sale->payments->where('is_bftn', true)->sum('received_amount'),
            'net_cash' => $this->settledAmount($sale)
                - $customerRefundPaid
                - $extraRefundPaid
                - (float) $ticketRefunds->sum('partner_share_amount'),
        ];
    }

    private function settledAmount(ShipTicketSale $sale): float
    {
        return (float) $sale->payments
            ->reject(fn ($payment): bool => $payment->is_bftn && $payment->settlement_status !== 'received')
            ->sum('received_amount');
    }
}
