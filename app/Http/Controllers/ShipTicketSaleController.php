<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShipTicketSale;

class ShipTicketSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = ShipTicketSale::latest()->get();
        return view('ship_ticket_sales.index', compact('sales'));
    }

     public function pendingCS()
    {
        $sales = ShipTicketSale::where('status','pending')->get();
        return response()->json($sales);
    }

      public function showPendingSales()
    {
        return view('ship_ticket_sales.pending_sales'); 
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ship_ticket_sales.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_mobile' => 'required|string|max:20',
            'sales_source' => 'nullable|string|max:255',
            'ship_name' => 'required|string|max:100',
            'journey_date' => 'required|date',
            'ticket_fee' => 'required|numeric',
            'payment_method' => 'required|string|max:255',
            'received_amount' => 'required|numeric',
            'due_amount' => 'nullable|numeric',
            'company_name' => 'required|string|max:100',
            'issued_date' => 'required|date',
            'sold_by' => 'required|string|max:100',
        ]);

        ShipTicketSale::create($request->all());

        return redirect()->back()->with('success', 'Ticket Sale Added Successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $sale = ShipTicketSale::findOrFail($id);
        return view('ship_ticket_sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $sale = ShipTicketSale::findOrFail($id);
        return view('ship_ticket_sales.edit', compact('sale'));
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id)
{  
    $request->validate([
        'customer_name' => 'nullable|string|max:100',
        'customer_mobile' => 'nullable|string|max:20',
        'status' => 'nullable|string|max:50',
    ]);

    $sale = ShipTicketSale::findOrFail($id);
   
   $test = $sale->update($request->only(['customer_name', 'customer_mobile', 'status']));

    return response()->json(['message' => 'Sale updated successfully']);
}


    /**
     * Remove the specified resource from storage.
     */
  public function destroy($id)
{
    try {
        // Find the sale by ID or fail
        $sale = ShipTicketSale::findOrFail($id);

        // Delete the sale
        $sale->delete();

        // Return success response
        return response()->json(['success' => true, 'message' => 'Sale deleted successfully']);
    } catch (\Exception $e) {
        // Handle the error if any, e.g., sale not found
        return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
    }
}


    public function checkDuplicate(Request $request)
{  
    $request->validate([
        'customer_mobile' => 'required|string',
        'journey_date' => 'required|date'
    ]);

    $existingTicket = ShipTicketSale::where('customer_mobile', $request->customer_mobile)
        ->where('journey_date', $request->journey_date)
        ->first();
 
    return response()->json([
        'exists' => $existingTicket !== null,
        'message' => $existingTicket 
            ? "This customer already has a ticket for {$request->journey_date} on {$existingTicket->ship_name}"
            : null
    ]);
}
}
