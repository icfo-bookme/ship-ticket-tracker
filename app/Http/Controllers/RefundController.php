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
