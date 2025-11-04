<?php

namespace App\Http\Controllers;

use App\Models\Ship;
use Illuminate\Http\Request;

class ShipController extends Controller
{

     public function showTableList()
    {
        return view('ships.componentItem');  
    }
    public function index()
    {
        $ships = Ship::all();
        return response()->json($ships);  
    }

    public function create()
    {
        return response()->json(['message' => 'Provide ship data to create'], 200);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'route' => 'required|string|max:255',
        'status' => 'required|string',
    ]);

    $ship = new Ship($validated);
    $ship->save();

    return response()->json($ship, 201); 
}


    public function show(Ship $ship)
    {
        return response()->json($ship);  
    }

    public function edit(Ship $ship)
    {
        return response()->json($ship);
    }


    public function update(Request $request, Ship $ship)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'route' => 'required|string|max:255',
        ]);

        $ship->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ship updated successfully',
            'data' => $ship
        ], 200);  
    }

   
    public function destroy(Ship $ship)
    {
        $ship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ship deleted successfully',
        ], 200);  
    }
}
