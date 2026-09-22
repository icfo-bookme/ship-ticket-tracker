<?php

namespace App\Services\MasterData;

use App\Models\ShipPackage;
use Illuminate\Http\Request;

class ShipPackageService
{
    public function dataTable(Request $request, int|string $shipId): array
    {
        $query = ShipPackage::where('ship_id', $shipId);
        $total = (clone $query)->count();
        if ($search = $request->input('search.value')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('price', 'like', "%{$search}%"));
        }
        $filtered = (clone $query)->count();
        $orderColumn = ['id', 'name', 'price', 'round_trip_price'][$request->integer('order.0.column', 0)] ?? 'id';
        $query->orderBy($orderColumn, $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc');
        $length = min(max($request->integer('length', 10), 1), 100);

        return ['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $query->skip($request->integer('start', 0))->take($length)->get()];
    }

    public function create(array $data): ShipPackage
    {
        return ShipPackage::create($data);
    }

    public function find(int|string $id): ShipPackage
    {
        $shipPackage = ShipPackage::find($id);
        abort_unless($shipPackage, 404, 'Ship package not found.');

        return $shipPackage;
    }

    public function update(ShipPackage $shipPackage, array $data): ShipPackage
    {
        $shipPackage->update($data);

        return $shipPackage;
    }
}
