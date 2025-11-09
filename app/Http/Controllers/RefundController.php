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

  public function refunded()
{
    try {
        
        $refunds = ShipTicketSale::with('refund')
            ->whereIn('status', ['refunded', 'partial-refunded'])
            ->get();

    
        if ($refunds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No refunds found for the given status.',
                'data' => [],
                'total_refunded_tickets' => 0,
                'total_refunded_amount' => 0,
            ], 200);
        }

        // Prepare arrays to hold refunded ticket numbers and amounts
       
        $totalRefundedTickets = 0;
        $totalRefundedAmount = 0;

        // Loop through the ShipTicketSale data and extract refund data
        foreach ($refunds as $sale) {
            foreach ($sale->refund as $refund) {
              
                // Calculate the totals
                $totalRefundedTickets += $refund->refunded_number_of_tickets;
                $totalRefundedAmount += $refund->refunded_amount;
            }
        }

        // Return the structured API response
        return response()->json([
            'status' => 'success',
            'message' => 'Refund data retrieved successfully.',
            'data' => $refunds,
            'total_refunded_tickets' => $totalRefundedTickets,
            'total_refunded_amount' => $totalRefundedAmount,
        ], 200);

    } catch (\Exception $e) {
        // Return a generic error message in case of any exception
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while retrieving refund data.',
            'error' => $e->getMessage(),
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

                $sale->status = 'partial-refunded';
                $sale->save();
            }

        return response()->json(['success' => true,'message' => 'Refund processed successfully.']);
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

    // Update the specified refund in storage
    public function update(Request $request, $id)
    {
        $request->validate([
            'sales_id' => 'required|integer',
            'refunded_number_of_tickets' => 'required|integer',
            'refunded_amount' => 'required|numeric',
        ]);

        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $refund->update($request->all());

        return response()->json($refund);
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
