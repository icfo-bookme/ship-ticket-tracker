<?php

namespace App\Http\Controllers;

use App\Models\ShipTicketSale;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

   public function reports(Request $request)
{
    try {
        $shipId = $request->input('ship_id');
        $companyId = $request->input('company_id');
        $journeyDate = $request->input('journey_date');
        $returnDate = $request->input('return_date');
        $paymentMethod = $request->input('payment_method');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $createdDate = $request->input('created_date');
        $start_create_date = $request->input('start_create_date');
        $end_create_date = $request->input('end_create_date');

        // DataTables parameters
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $searchValue = $request->input('search.value', '');
        $draw = $request->input('draw', 1);
        $orderColumn = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        $query = ShipTicketSale::with(['ships', 'companies', 'refund'])
            ->where('status', '!=', 'pending');

        // Apply filters
        if (!empty($shipId)) $query->where('ship_id', $shipId);
        if (!empty($companyId)) $query->where('company_id', $companyId);
        if (!empty($journeyDate)) $query->whereDate('journey_date', $journeyDate);
        if (!empty($returnDate)) $query->whereDate('return_date', $returnDate);
        if (!empty($createdDate)) $query->whereDate('created_at', $createdDate);
        if (!empty($paymentMethod)) $query->where(function ($q) use ($paymentMethod) {
            $q->where('payment_method', $paymentMethod)
                ->orWhereHas('payments', function ($sub) use ($paymentMethod) {
                    $sub->where('payment_method', $paymentMethod);
                });
        });
        if (!empty($startDate)) $query->whereDate('journey_date', '>=', $startDate);
        if (!empty($endDate)) $query->whereDate('journey_date', '<=', $endDate);
        if (!empty($start_create_date)) $query->whereDate('created_at', '>=', $start_create_date);
        if (!empty($end_create_date)) $query->whereDate('created_at', '<=', $end_create_date);

        // Total records count (without filtering)
        $totalRecords = clone $query;
        $totalRecords = $totalRecords->count();

        // Search functionality
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('customer_name', 'like', "%{$searchValue}%")
                    ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('nid', 'like', "%{$searchValue}%")
                    ->orWhere('sales_source', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                    ->orWhere('payment_method', 'like', "%{$searchValue}%")
                    ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('received_amount', 'like', "%{$searchValue}%")
                    ->orWhere('due_amount', 'like', "%{$searchValue}%")
                    ->orWhere('sold_by', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_category', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereDate('journey_date', $searchValue)
                    ->orWhereDate('return_date', $searchValue)
                    ->orWhereDate('issued_date', $searchValue)
                    ->orWhereDate('created_at', $searchValue)
                    ->orWhere('id', $searchValue)
                    ->orWhereHas('ships', function ($shipQuery) use ($searchValue) {
                        $shipQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('companies', function ($companyQuery) use ($searchValue) {
                        $companyQuery->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Filtered records count
        $filteredRecords = clone $query;
        $filteredRecords = $filteredRecords->count();

        // Ordering
        $orderableColumns = [
            'id',
            'customer_name',
            'customer_mobile',
            'journey_date',
            'number_of_ticket',
            'received_amount',
            'status',
            'created_at'
        ];

        if (isset($orderableColumns[$orderColumn])) {
            $query->orderBy($orderableColumns[$orderColumn], $orderDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $sales = $query->skip($start)
            ->take($length)
            ->get();

        // Calculate totals for all filtered records using SQL aggregates (not loading every row).
        $totalsQuery = ShipTicketSale::query()
            ->where('ship_ticket_sales.status', '!=', 'pending')
            ->leftJoin('refunds', 'refunds.sales_id', '=', 'ship_ticket_sales.id');

        // Reapply the same filters for accurate totals (all qualified to ship_ticket_sales to avoid ambiguity)
        if (!empty($shipId)) $totalsQuery->where('ship_ticket_sales.ship_id', $shipId);
        if (!empty($companyId)) $totalsQuery->where('ship_ticket_sales.company_id', $companyId);
        if (!empty($journeyDate)) $totalsQuery->whereDate('ship_ticket_sales.journey_date', $journeyDate);
        if (!empty($returnDate)) $totalsQuery->whereDate('ship_ticket_sales.return_date', $returnDate);
        if (!empty($createdDate)) $totalsQuery->whereDate('ship_ticket_sales.created_at', $createdDate);
        if (!empty($paymentMethod)) $totalsQuery->where(function ($q) use ($paymentMethod) {
            $q->where('ship_ticket_sales.payment_method', $paymentMethod)
                ->orWhereHas('payments', function ($sub) use ($paymentMethod) {
                    $sub->where('payment_method', $paymentMethod);
                });
        });
        if (!empty($startDate)) $totalsQuery->whereDate('ship_ticket_sales.journey_date', '>=', $startDate);
        if (!empty($endDate)) $totalsQuery->whereDate('ship_ticket_sales.journey_date', '<=', $endDate);
        if (!empty($start_create_date)) $totalsQuery->whereDate('ship_ticket_sales.created_at', '>=', $start_create_date);
        if (!empty($end_create_date)) $totalsQuery->whereDate('ship_ticket_sales.created_at', '<=', $end_create_date);

        $totals = (clone $totalsQuery)
            ->selectRaw('
                COALESCE(SUM(ship_ticket_sales.number_of_ticket), 0) AS total_number_of_tickets,
                COALESCE(SUM(ship_ticket_sales.ticket_fee), 0)      AS total_ticket_fee,
                COALESCE(SUM(ship_ticket_sales.other_fee), 0)       AS total_other_fee,
                COALESCE(SUM(ship_ticket_sales.total_payable), 0)   AS total_payable,
                COALESCE(SUM(ship_ticket_sales.received_amount), 0) AS total_received_amount,
                COALESCE(SUM(ship_ticket_sales.due_amount), 0)      AS total_due_amount,
                COALESCE(SUM(refunds.refunded_number_of_tickets), 0) AS total_refunded_tickets,
                COALESCE(SUM(refunds.refunded_amount), 0)            AS total_refunded_amount
            ')
            ->first();

        $totalNumberOfTickets = $totals->total_number_of_tickets;
        $totalTicketFee      = $totals->total_ticket_fee;
        $totalOtherFee       = $totals->total_other_fee;
        $totalPayable        = $totals->total_payable;
        $totalReceivedAmount = $totals->total_received_amount;
        $totalDueAmount      = $totals->total_due_amount;
        $totalRefundedTickets = $totals->total_refunded_tickets;
        $totalRefundedAmount  = $totals->total_refunded_amount;

        $netSalesAmount = $totalReceivedAmount - $totalRefundedAmount;

        // Format data for DataTables
        $formattedData = $sales->map(function ($sale) {
            $refundStatus = 'No Refund';
            $refundedTickets = 0;
            $refundedAmount = 0;

            if ($sale->refund) {
                $refundedTickets = (int) $sale->refund->refunded_number_of_tickets;
                $refundedAmount = (float) $sale->refund->refunded_amount;

                if ($refundedTickets >= $sale->number_of_ticket) {
                    $refundStatus = 'Full Refund';
                } elseif ($refundedTickets > 0) {
                    $refundStatus = 'Partial Refund';
                }
            }

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
                'other_fee' => $sale->other_fee,
                'total_payable' => $sale->total_payable,
                'due_amount' => $sale->due_amount,
                'refunded_number_of_tickets' => $refundedTickets,
                'status' => $sale->status,
                'payment_method' => $sale->payment_method,
                'created_at' => $sale->created_at,
                'refund_status' => $refundStatus,
                'refunded_tickets' => $refundedTickets,
                'refunded_amount' => $refundedAmount,
                'net_amount' => $sale->received_amount - $refundedAmount,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $formattedData,
            'totals' => [
                'total_number_of_tickets' => $totalNumberOfTickets,
                'total_ticket_fee'        => number_format($totalTicketFee, 2),
                'total_other_fee'         => number_format($totalOtherFee, 2),
                'total_payable'           => number_format($totalPayable, 2),
                'total_received_amount'   => number_format($totalReceivedAmount, 2),
                'total_refunded_tickets'  => $totalRefundedTickets,
                'total_refunded_amount'   => number_format($totalRefundedAmount, 2),
                'total_due_amount'        => number_format($totalDueAmount, 2),
                'net_sales_amount'        => number_format($netSalesAmount, 2),
            ]

        ], 200);
    } catch (\Throwable $e) {
        // Log the error for debugging
        \Log::error('Report generation error: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'totals' => [
                'total_number_of_tickets' => 0,
                'total_ticket_fee'        => '0.00',
                'total_other_fee'         => '0.00',
                'total_payable'           => '0.00',
                'total_received_amount'   => '0.00',
                'total_refunded_tickets'  => 0,
                'total_refunded_amount'   => '0.00',
                'total_due_amount'        => '0.00',
                'net_sales_amount'        => '0.00',
            ],
            'error' => 'An error occurred while generating the report.'
        ], 500);
    }
}
}
