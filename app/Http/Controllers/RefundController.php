<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;

class RefundController extends Controller
{

    public function index()
    {
        $refunds = Refund::all();
        return response()->json($refunds);
    }


    public function create()
    {
        return view('refund.componentItem');
    }

    public function refunded(Request $request)
    {
        $shipId = $request->input('ship_id');
        $companyId = $request->input('company_id');
        $journeyDate = $request->input('journey_date');

        try {
            // Build the query with relationships
            $query = ShipTicketSale::with(['ships', 'companies', 'refund'])
                ->whereIn('status', ['refunded', 'partial-refunded']);

            // Apply filters if provided
            if ($shipId && !empty($shipId)) {
                $query->where('ship_id', $shipId);
            }

            if ($companyId && !empty($companyId)) {
                $query->where('company_id', $companyId);
            }

            if ($journeyDate && !empty($journeyDate)) {
                $query->whereDate('journey_date', $journeyDate);
            }

            $sales = $query->get();

            // Handle case when no refunds are found
            if ($sales->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'No refunded or partially refunded tickets found.',
                    'data'    => [],
                    'total_refunded_tickets' => 0,
                    'total_refunded_amount'  => 0,
                ], 200);
            }

            // Calculate totals
            $totalRefundedTickets = 0;
            $totalRefundedAmount  = 0;

            foreach ($sales as $sale) {
                if ($sale->refund) {
                    $totalRefundedTickets += (int) $sale->refund->refunded_number_of_tickets;
                    $totalRefundedAmount  += (float) $sale->refund->refunded_amount;
                }
            }

            // Return structured response
            return response()->json([
                'status'  => 'success',
                'message' => 'Refund data retrieved successfully.',
                'data'    => $sales,
                'total_refunded_tickets' => $totalRefundedTickets,
                'total_refunded_amount'  => $totalRefundedAmount,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An unexpected error occurred while retrieving refund data.',
                'error'   => $e->getMessage() // optional: for debugging
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $request->validate([
            'sales_id' => 'required|integer',
            'refunded_number_of_tickets' => 'required|integer',
            'refunded_amount' => 'required|numeric',
        ]);

        $refund = Refund::create($request->all());

        return response()->json($refund, 201);
    }

    public function showRefundedCS()
    {
        return view('refunded.index');
    }

    public function fullRefunds(Request $request)
    {

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        foreach ($validated['ids'] as $id) {

            $sale = ShipTicketSale::find($id);
            if ($sale && $sale->status == 'shipped') {

                Refund::create([
                    'sales_id' => $sale->id,
                    'refunded_number_of_tickets' => $sale->number_of_ticket,
                    'refunded_amount' => $sale->received_amount,
                ]);

                $sale->status = 'refunded';
                $sale->save();
            }
        }
        return response()->json(['message' => 'Refund processed successfully.']);
    }

    public function partialRefund(Request $request, $id)
    {

        $sale = ShipTicketSale::find($id);
        if ($sale && $sale->status == 'shipped') {

            Refund::create([
                'sales_id' => $id,
                'refunded_number_of_tickets' => $request->refunded_number_of_tickets,
                'refunded_amount' => $request->refunded_amount,
            ]);

            if ($sale->number_of_ticket == $request->refunded_number_of_tickets) {
                $sale->status = 'refunded';
            } else {
                $sale->status = 'partial-refunded';
            }
            $sale->save();
        }

        return response()->json(['success' => true, 'message' => 'Refund processed successfully.']);
    }



    public function show($id)
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        return response()->json($refund);
    }


    public function edit($id)
    {
        // Not necessary for APIs, usually handled in web apps
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'refunded_number_of_tickets' => 'required|integer|min:1',
            'refunded_amount' => 'required|numeric|min:0',
        ]);

        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $sale = ShipTicketSale::find($refund->sales_id);

        if (!$sale) {
            return response()->json(['message' => 'Associated sale not found'], 404);
        }

        $refund->update([
            'refunded_number_of_tickets' => $request->refunded_number_of_tickets,
            'refunded_amount' => $request->refunded_amount,
        ]);

        $sale->status = ($sale->number_of_ticket == $request->refunded_number_of_tickets)
            ? 'refunded'
            : 'partial-refunded';
        $sale->save();

        return response()->json(['success' => true, 'message' => 'Refund updated successfully.']);
    }


    // Remove the specified refund from storage
    public function destroy($id)
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $refund->delete();

        return response()->json(['message' => 'Refund deleted successfully']);
    }
}
