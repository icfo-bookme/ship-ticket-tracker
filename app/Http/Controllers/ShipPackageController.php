<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterData\StoreShipPackageRequest;
use App\Http\Requests\MasterData\UpdateShipPackageRequest;
use App\Models\ShipPackage;
use App\Services\MasterData\ShipPackageService;
use Illuminate\Http\Request;

class ShipPackageController extends Controller
{
    public function __construct(private readonly ShipPackageService $shipPackages) {}

    public function index(Request $request, $id)
    {
        return response()->json($this->shipPackages->dataTable($request, $id));
    }

    public function showPackages($id)
    {
        return view('packages.componentItem', compact('id'));
    }

    public function store(StoreShipPackageRequest $request)
    {
        $package = $this->shipPackages->create($request->validated());

        return response()->json($package, 201);
    }

    public function show(ShipPackage $shipPackage)
    {
        return response()->json($shipPackage);
    }

    public function edit(string $id)
    {
        return response()->json($this->shipPackages->find($id));
    }

    public function update(UpdateShipPackageRequest $request, $id)
    {
        $shipPackage = $this->shipPackages->update($this->shipPackages->find($id), $request->validated());

        return response()->json($shipPackage);
    }

    public function destroy($id)
    {
        $shipPackage = $this->shipPackages->find($id);
        $shipPackage->delete();

        return response()->json(['success' => true, 'message' => 'Ship package deleted successfully.']);
    }
}
