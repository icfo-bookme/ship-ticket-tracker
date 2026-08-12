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
        return response()->json(Ship::all());
    }

    public function create()
    {
        return response()->json(['message' => 'Provide ship data to create.'], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'status' => 'required|string',
        ]);

        $ship = Ship::create($validated);

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'status' => 'required',
        ]);

        $ship->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ship updated successfully.',
            'data' => $ship,
        ], 200);
    }

    public function destroy(Ship $ship)
    {
        $ship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ship deleted successfully.',
        ], 200);
    }
}
