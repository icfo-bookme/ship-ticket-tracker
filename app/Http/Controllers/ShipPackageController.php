<?php

namespace App\Http\Controllers;

use App\Models\ShipPackage;
use Illuminate\Http\Request;

class ShipPackageController extends Controller
{
    public function index($id)
    {
        $packages = ShipPackage::where('ship_id', $id)->get();

        return response()->json($packages);
    }

    public function showPackages($id)
    {
        return view('packages.componentItem', compact('id'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250',
            'ship_id' => 'required|integer|exists:ships,id',
            'price' => 'required|numeric|min:0',
            'round_trip_price' => 'required|numeric|min:0',
        ]);

        $package = ShipPackage::create($validated);

        return response()->json($package, 201);
    }

    public function show(ShipPackage $shipPackage)
    {
        return response()->json($shipPackage);
    }

    public function edit(string $id)
    {
        $shipPackage = ShipPackage::find($id);
        abort_unless($shipPackage, 404, 'Ship package not found.');

        return response()->json($shipPackage);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250',
            'price' => 'required|numeric|min:0',
            'round_trip_price' => 'required|numeric|min:0',
        ]);

        $shipPackage = ShipPackage::find($id);
        abort_unless($shipPackage, 404, 'Ship package not found.');

        $shipPackage->update($validated);

        return response()->json($shipPackage);
    }

    public function destroy($id)
    {
        $shipPackage = ShipPackage::find($id);
        abort_unless($shipPackage, 404, 'Ship package not found.');

        $shipPackage->delete();

        return response()->json(['success' => true, 'message' => 'Ship package deleted successfully.']);
    }
}
