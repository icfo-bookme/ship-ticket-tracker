<?php

namespace App\Http\Controllers;

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
        return view('cashCollection.componentItem'); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    { dd($request->all());
        $request->validate([
            'name'           => 'nullable|string|max:255',
            'entry_by'       => 'required|integer',
            'cashout_amount' => 'required|numeric',
        ]);

        $collection = CashCollection::create([
            'name'           => $request->name,
            'entry_by'       => $request->entry_by,
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
            'entry_by'       => 'required|integer',
            'cashout_amount' => 'required|numeric',
        ]);

        $collection = CashCollection::findOrFail($id);

        $collection->update([
            'name'           => $request->name,
            'entry_by'       => $request->entry_by,
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
