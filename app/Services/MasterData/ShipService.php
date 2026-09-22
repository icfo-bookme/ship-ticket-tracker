<?php

namespace App\Services\MasterData;

use App\Models\Ship;
use Illuminate\Http\Request;

class ShipService
{
    public function dataTable(Request $request): array
    {
        $query = Ship::query();
        $total = (clone $query)->count();
        if ($search = $request->input('search.value')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('route', 'like', "%{$search}%"));
        }
        $filtered = (clone $query)->count();
        $orderColumn = ['id', 'name', 'route', 'status'][$request->integer('order.0.column', 0)] ?? 'id';
        $query->orderBy($orderColumn, $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc');
        $length = min(max($request->integer('length', 10), 1), 100);

        return ['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $query->skip($request->integer('start', 0))->take($length)->get()];
    }

    public function create(array $data): Ship
    {
        return Ship::create($data);
    }

    public function update(Ship $ship, array $data): Ship
    {
        $ship->update($data);

        return $ship;
    }
}
