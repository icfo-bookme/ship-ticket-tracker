<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShipTicketSale;
use App\Models\Ship;
use App\Models\CoPassenger;
use App\Models\Shipment;
use App\Models\Company;

class ShipTicketSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = ShipTicketSale::with('ships')->latest()->get();
        $ships = Ship::all();
        return view('ship_ticket_sales.index', compact('sales', 'ships'));
    }

    public function pendingCS(Request $request, $status)
    {
        $shipId = $request->input('ship_id');
        $companyId = $request->input('company_id');
        $journeyDate = $request->input('journey_date');

        $query = ShipTicketSale::with('ships', 'companies')->where('status', $status);

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

        return response()->json($sales);
    }


    public function showPendingSales($status)
    {
        return view('ship_ticket_sales.index', compact('status'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ships = Ship::all();
        $companies = Company::all();
        return view('ship_ticket_sales.create', compact('ships', 'companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
{ dd($request->all());
    $validated = $request->validate([
        'customer_name'   => 'required|string|max:100',
        'customer_mobile' => 'required|string|max:20',
        'nid'             => 'nullable|string|max:50',
        'email'           => 'nullable|string|max:100',
        'sales_source'    => 'nullable|string|max:255',
        'ship_id'         => 'required|string|max:100',
        'journey_date'    => 'nullable|date',
        'date_of_birth'   => 'nullable|date',
        'return_date'     => 'required|date',
        'ticket_fee'      => 'required|numeric',
        'payment_method'  => 'required|string|max:255',
        'received_amount' => 'required|numeric',
        'number_of_ticket'=> 'required|numeric',
        'ticket_category' => 'nullable|string|max:255',
        'due_amount'      => 'nullable|numeric',
        'company_id'      => 'required|string|max:100',
        'issued_date'     => 'required|date',
        'sold_by'         => 'required|string|max:100',
    ]);

    $ticketSale = ShipTicketSale::create($validated);

    if ($request->filled('co_passengers')) {
        foreach ($request->co_passengers as $coPassenger) {
            if (!empty($coPassenger['name']) && !empty($coPassenger['nid'])) {
                CoPassenger::create([
                    'ship_ticket_sale_id' => $ticketSale->id,
                    'name' => $coPassenger['name'],
                    'nid'  => $coPassenger['nid'],
                ]);
            }
        }
    }

   

   return redirect()->route('ship-ticket-sales.show', [$ticketSale->id])
                 ->with('success', 'Journey ticket saved! You can now fill return ticket.');

}


    /**
     * Display the specified resource.
     */
    public function show($id)
    { 
        $sale = ShipTicketSale::findOrFail($id);
         $ships = Ship::all();
        $companies = Company::all();
        return view('ship_ticket_sales.create', compact('sale', 'ships', 'companies'));
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
            'date_of_birth' => 'nullable|date',
            'sales_source' => 'nullable|string|max:20',
            'ship_id' => 'nullable|exists:ships,id',
            'journey_date' => 'nullable|date',
            'return_date' => 'required|date',
            'ticket_fee' => 'nullable|numeric|min:0',
            'nid' => 'nullable',
            'email' => 'string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'received_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'nullable|numeric|min:0',
            'company_id' => 'nullable',
            'number_of_ticket' => 'required|numeric',
            'issued_date' => 'nullable|date',
            'ticket_category' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|string|max:50',
        ]);

        $sale = ShipTicketSale::findOrFail($id);

        $test = $sale->update($request->only(['customer_name', 'customer_mobile', 'payment_method', 'received_amount', 'due_amount', 'company_id', 'issued_date', 'status', 'sales_source', 'ship_id', 'journey_date', 'ticket_fee', 'nid', 'email', 'number_of_ticket', 'return_date', 'ticket_category']));

        return response()->json(['message' => 'Sale updated successfully']);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
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
                ? "This customer already has a ticket for {$request->journey_date} on {$existingTicket->sales_source}"
                : null
        ]);
    }

    public function verify(Request $request, $id, $status)
    {
        if ($request->shipmentId) {
            $shipment = new Shipment();
            $shipment->ticket_id =  $id;
            $shipment->shipment_id = $request->shipmentId;
            $shipment->save();
        }
        $sale = ShipTicketSale::findOrFail($id);
        $sale->update(['status' => $status]);

        return response()->json(['success' => true, 'message' => 'Sale deleted successfully']);
    }
}
