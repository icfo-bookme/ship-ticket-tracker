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

    try {

        $query = ShipTicketSale::with(['ships', 'companies', 'refund'])
            ->where('status', '!=', 'pending'); 

        if (!empty($shipId)) $query->where('ship_id', $shipId);
        if (!empty($companyId)) $query->where('company_id', $companyId);
        if (!empty($journeyDate)) $query->whereDate('journey_date', $journeyDate);
        if (!empty($returnDate)) $query->whereDate('return_date', $returnDate);
        if (!empty($createdDate)) $query->whereDate('created_at', $createdDate);
        if (!empty($paymentMethod)) $query->where('payment_method', $paymentMethod);
        if (!empty($startDate)) $query->whereDate('journey_date', '>=', $startDate);
        if (!empty($endDate)) $query->whereDate('journey_date', '<=', $endDate);
        if (!empty($start_create_date)) $query->whereDate('created_at', '>=', $start_create_date);
        if (!empty($end_create_date)) $query->whereDate('created_at', '<=', $end_create_date);

        $sales = $query->get();

        if ($sales->isEmpty()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'No sales found for the selected filters.',
                'data'    => [],
                'total_sold_tickets' => 0,
                'total_sales_amount' => 0,
                'total_refunded_tickets' => 0,
                'total_refunded_amount'  => 0,
            ], 200);
        }

        // Initialize totals
        $totalSoldTickets = $sales->sum('number_of_ticket');
        $totalSalesAmount = $sales->sum('received_amount');
        $totalRefundedTickets = 0;
        $totalRefundedAmount  = 0;

        // Process each sale for refund details
        $sales = $sales->map(function ($sale) use (&$totalRefundedTickets, &$totalRefundedAmount) {

            $refundStatus = 'No Refund';
            $refundedTickets = 0;
            $refundedAmount = 0;

            if ($sale->refund) {
                $refundedTickets = (int) $sale->refund->refunded_number_of_tickets;
                $refundedAmount  = (float) $sale->refund->refunded_amount;
                $totalRefundedTickets += $refundedTickets;
                $totalRefundedAmount  += $refundedAmount;

                // Determine refund type
                if ($refundedTickets >= $sale->number_of_tickets) {
                    $refundStatus = 'Full Refund';
                } elseif ($refundedTickets > 0) {
                    $refundStatus = 'Partial Refund';
                }
            }

            // Add computed data to each record
            $sale->refund_status = $refundStatus;
            $sale->refunded_tickets = $refundedTickets;
            $sale->refunded_amount = $refundedAmount;

            return $sale;
        });

        // Return structured response
        return response()->json([
            'status'  => 'success',
            'message' => 'Report generated successfully.',
            'data'    => $sales,
            'total_sold_tickets' => $totalSoldTickets,
            'total_sales_amount' => $totalSalesAmount,
            'total_refunded_tickets' => $totalRefundedTickets,
            'total_refunded_amount'  => $totalRefundedAmount,
            'net_sales_amount' => $totalSalesAmount - $totalRefundedAmount, 
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'An unexpected error occurred while retrieving report data.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

}
