<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;

class SalesReportService
{
    public function dataTable(Request $request): array
    {
        $filters = $this->filters($request);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $searchValue = $request->input('search.value', '');
        $draw = $request->input('draw', 1);
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        $query = ShipTicketSale::with(['ships', 'companies', 'refunds', 'payments', 'bftn'])
            ->where('status', '!=', SaleStatus::Pending->value);

        $this->applyFilters($query, $filters);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $this->applySearch($query, $searchValue);
        }

        $filteredRecords = (clone $query)->count();
        $this->applyOrdering($query, $orderColumn, $orderDirection);

        $sales = $query->skip($start)->take($length)->get();
        $totals = $this->totals($filters);

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
                'total_received_amount' => number_format($totals->total_received_amount, 2),
                'total_extra_received_amount' => number_format($totals->total_extra_received_amount, 2),
                'total_extra_refunded_amount' => number_format($totals->total_extra_refunded_amount, 2),
                'total_refunded_tickets' => $totals->total_refunded_tickets,
                'total_refunded_amount' => number_format($totals->total_refunded_amount, 2),
                'total_due_amount' => number_format($totals->total_due_amount, 2),
                'total_gross_refund_amount' => number_format($totals->total_gross_refund_amount, 2),
                'total_customer_refund_amount' => number_format($totals->total_customer_refund_amount, 2),
                'total_partner_share_amount' => number_format($totals->total_partner_share_amount, 2),
                'total_company_retained_amount' => number_format($totals->total_company_retained_amount, 2),
                'total_bftn' => $totals->total_bftn,
                'total_bftn_pending' => $totals->total_bftn_pending,
                'total_bftn_received' => $totals->total_bftn_received,
                'total_bftn_amount' => number_format($totals->total_bftn_amount, 2),
                'total_bftn_pending_amount' => number_format($totals->total_bftn_pending_amount, 2),
                'total_bftn_received_amount' => number_format($totals->total_bftn_received_amount, 2),
                'net_cash' => number_format($totals->total_received_amount - $totals->total_bftn_pending_amount - $totals->total_customer_refund_amount, 2),
                'net_sales_amount' => number_format($totals->total_received_amount - $totals->total_refunded_amount, 2),
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
                'total_received_amount' => '0.00',
                'total_extra_received_amount' => '0.00',
                'total_extra_refunded_amount' => '0.00',
                'total_refunded_tickets' => 0,
                'total_refunded_amount' => '0.00',
                'total_due_amount' => '0.00',
                'total_gross_refund_amount' => '0.00',
                'total_customer_refund_amount' => '0.00',
                'total_partner_share_amount' => '0.00',
                'total_company_retained_amount' => '0.00',
                'total_bftn' => 0,
                'total_bftn_pending' => 0,
                'total_bftn_received' => 0,
                'total_bftn_amount' => '0.00',
                'total_bftn_pending_amount' => '0.00',
                'total_bftn_received_amount' => '0.00',
                'net_cash' => '0.00',
                'net_sales_amount' => '0.00',
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

        foreach ([
            'ship_id' => 'ship_id',
            'company_id' => 'company_id',
        ] as $filter => $columnName) {
            if (! empty($filters[$filter])) {
                $query->where($column($columnName), $filters[$filter]);
            }
        }

        foreach ([
            'journey_date' => ['journey_date', '='],
            'return_date' => ['return_date', '='],
            'created_date' => ['created_at', '='],
            'start_date' => ['journey_date', '>='],
            'end_date' => ['journey_date', '<='],
            'start_create_date' => ['created_at', '>='],
            'end_create_date' => ['created_at', '<='],
        ] as $filter => [$columnName, $operator]) {
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

    private function totals(array $filters): object
    {
        $query = ShipTicketSale::query()
            ->where('ship_ticket_sales.status', '!=', SaleStatus::Pending->value);

        $this->applyFilters($query, $filters, 'ship_ticket_sales.');

        return $query->selectRaw('
            COALESCE(SUM(ship_ticket_sales.number_of_ticket), 0) AS total_number_of_tickets,
            COALESCE(SUM(ship_ticket_sales.ticket_fee), 0) AS total_ticket_fee,
            COALESCE(SUM(ship_ticket_sales.other_fee), 0) AS total_other_fee,
            COALESCE(SUM(ship_ticket_sales.discount_amount), 0) AS total_discount_amount,
            COALESCE(SUM(ship_ticket_sales.total_payable), 0) AS total_payable,
            COALESCE(SUM(ship_ticket_sales.received_amount), 0) AS total_received_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.received_amount > ship_ticket_sales.total_payable THEN ship_ticket_sales.received_amount - ship_ticket_sales.total_payable ELSE 0 END), 0) AS total_extra_received_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(customer_refund_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND refunds.refund_type = "extra_payment" AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_extra_refunded_amount,
            COALESCE(SUM(ship_ticket_sales.due_amount), 0) AS total_due_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" THEN 1 ELSE 0 END), 0) AS total_bftn,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 0 THEN 1 ELSE 0 END), 0) AS total_bftn_pending,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 1 THEN 1 ELSE 0 END), 0) AS total_bftn_received,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS total_bftn_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 0 THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS total_bftn_pending_amount,
            COALESCE(SUM(CASE WHEN ship_ticket_sales.bftn_status = "yes" AND COALESCE((SELECT received_status FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id LIMIT 1), 0) = 1 THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS total_bftn_received_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(refunded_number_of_tickets), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_refunded_tickets,
            COALESCE(SUM((SELECT COALESCE(SUM(refunded_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_refunded_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(gross_refund_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_gross_refund_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(customer_refund_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_customer_refund_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(partner_share_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_partner_share_amount,
            COALESCE(SUM((SELECT COALESCE(SUM(company_retained_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))), 0) AS total_company_retained_amount
        ')->first();
    }

    private function formatSale(ShipTicketSale $sale): array
    {
        $completedRefunds = $sale->refunds->filter(
            fn ($refund): bool => $refund->status === 'completed' || $refund->customer_refunded_at !== null
        );
        $refundedTickets = (int) $completedRefunds->sum('refunded_number_of_tickets');
        $refundedAmount = (float) $completedRefunds->sum('refunded_amount');
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
            'number_of_ticket' => $sale->number_of_ticket,
            'ticket_fee' => $sale->ticket_fee,
            'received_amount' => $sale->received_amount,
            'extra_received_amount' => max((float) $sale->received_amount - (float) $sale->total_payable, 0),
            'extra_refunded_amount' => (float) $completedRefunds->where('refund_type', 'extra_payment')->sum('customer_refund_amount'),
            'other_fee' => $sale->other_fee,
            'discount_amount' => $sale->discount_amount,
            'total_payable' => $sale->total_payable,
            'due_amount' => $sale->due_amount,
            'refunded_number_of_tickets' => $refundedTickets,
            'status' => $sale->status,
            'payment_method' => $sale->payments->first()->payment_method ?? null,
            'created_at' => $sale->created_at,
            'refund_status' => $refundStatus,
            'refunded_tickets' => $refundedTickets,
            'refunded_amount' => $refundedAmount,
            'gross_refund_amount' => (float) $completedRefunds->sum('gross_refund_amount'),
            'customer_charge_percent' => $completedRefunds->last()?->customer_charge_percent,
            'partner_share_percent' => $completedRefunds->last()?->partner_share_percent,
            'customer_refund_amount' => (float) $completedRefunds->sum('customer_refund_amount'),
            'partner_share_amount' => (float) $completedRefunds->sum('partner_share_amount'),
            'company_retained_amount' => (float) $completedRefunds->sum('company_retained_amount'),
            'bftn_status' => $sale->bftn_status,
            'bftn_received' => $sale->bftn_status === 'yes' && (bool) $sale->bftn?->received_status,
            'bftn_amount' => $sale->bftn_status === 'yes' ? (float) $sale->received_amount : 0,
            'net_cash' => (float) $sale->received_amount - (float) $completedRefunds->sum('customer_refund_amount'),
        ];
    }
}
