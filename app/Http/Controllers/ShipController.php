<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterData\StoreShipRequest;
use App\Http\Requests\MasterData\UpdateShipRequest;
use App\Models\Ship;
use App\Services\MasterData\ShipService;
use Illuminate\Http\Request;

class ShipController extends Controller
{
    public function __construct(private readonly ShipService $ships) {}

    public function showTableList()
    {
        return view('ships.componentItem');
    }

    public function index(Request $request)
    {
        return response()->json($this->ships->dataTable($request));
    }

    public function create()
    {
        return response()->json(['message' => 'Provide ship data to create.'], 200);
    }

    public function store(StoreShipRequest $request)
    {
        $ship = $this->ships->create($request->validated());

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

    public function update(UpdateShipRequest $request, Ship $ship)
    {
        $ship = $this->ships->update($ship, $request->validated());

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
