<?php

namespace App\Http\Controllers;
use App\Models\ShipTicketSale;
use App\Models\CashCollection;
use Illuminate\Http\Request;

class CashCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $collections = CashCollection::latest()->get();
        return response()->json($collections);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function showCashCollection()
    {
        // Total cash received from paid (non-pending) sales — SQL aggregate, no full-table load.
        $totalReceivedAmount = (float) ShipTicketSale::where('status', '!=', 'pending')
            ->sum('received_amount');

        // Total refunded back to customers.
        $totalRefundedAmount = (float) ShipTicketSale::query()
            ->leftJoin('refunds', 'refunds.sales_id', '=', 'ship_ticket_sales.id')
            ->where('ship_ticket_sales.status', '!=', 'pending')
            ->sum('refunds.refunded_amount');

        // Total cash already taken out of the drawer (cash collections ledger).
        $totalCashedOutAmount = (float) CashCollection::sum('cashout_amount');

        // Available cash = what came in - refunds - what is already cashed out.
        $availableCashAmount = $totalReceivedAmount - $totalRefundedAmount - $totalCashedOutAmount;

        return view('cashCollection.componentItem', compact(
            'availableCashAmount',
            'totalReceivedAmount',
            'totalRefundedAmount',
            'totalCashedOutAmount'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'nullable|string|max:255',
            'cashout_amount' => 'required|numeric',
        ]);

        $collection = CashCollection::create([
            'name'           => $request->name,
            'entry_by'       => auth()->id(),
            'cashout_amount' => $request->cashout_amount,
        ]);

        return response()->json([
            'message' => 'Cash collection created successfully.',
            'data'    => $collection
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $collection = CashCollection::findOrFail($id);
        return response()->json($collection);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'           => 'nullable|string|max:255',
            'cashout_amount' => 'required|numeric',
        ]);

        $collection = CashCollection::findOrFail($id);

        $collection->update([
            'name'           => $request->name,
            'entry_by'       => $collection->entry_by ?? auth()->id(),
            'cashout_amount' => $request->cashout_amount,
        ]);

        return response()->json([
            'message' => 'Cash collection updated successfully.',
            'data'    => $collection
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $collection = CashCollection::findOrFail($id);
        $collection->delete();

        return response()->json([
            'message' => 'Cash collection deleted successfully.'
        ]);
    }
}
