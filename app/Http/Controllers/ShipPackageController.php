<?php

namespace App\Http\Controllers;
use App\Models\ShipPackage;
use Illuminate\Http\Request;

class ShipPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    { 
        // Get all ship packages
        $packages = ShipPackage::where('ship_id', $id)->get();
        
        // Return the packages as JSON
        return response()->json($packages);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function showPackages( $id)
    { 
        return view('packages.componentItem', compact('id'));  
    }

    /**
     * Store a newly created resource in storage.
     */
     public function store(Request $request)
    { 
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:250',
            'ship_id' => 'required|integer|exists:ships,id', 
             'price' => 'required|numeric|min:0',
             'round_trip_price' => 'required|numeric|min:0',
        ]);

        // Create the new ship package
        $package = ShipPackage::create([
            'name' => $validated['name'],
            'ship_id' => $validated['ship_id'],
            'price' => $validated['price'],
            'round_trip_price' => $validated['round_trip_price'],
        ]);

        // Return the newly created package as JSON with 201 status code
        return response()->json($package, 201);
    }

    /**
     * Display the specified resource.
     */
   public function show(ShipPackage $shipPackage)
    {
        // Return the ship package as JSON
        return response()->json($shipPackage);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id)
    {  
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:250',
            'price' => 'required|numeric|min:0',
            'round_trip_price' => 'required|numeric|min:0',

        ]);

        $shipPackage = ShipPackage::find($id);
        
        // Update the ship package
        $shipPackage->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'round_trip_price' => $validated['round_trip_price'],
        ]);

        // Return the updated ship package as JSON
        return response()->json($shipPackage);
    }

    /**
     * Remove the specified resource from storage.
     */
   public function destroy($id)
    {
        $shipPackage = ShipPackage::find($id);
        $shipPackage->delete();

        // Return a success message as JSON
        return response()->json(['success' => true, 'message' => 'Ship package deleted successfully']);
    }
}
