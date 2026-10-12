<?php

namespace App\Services\MasterData;

use App\Models\SalesSource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalesSourceService
{
    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: Collection<int, SalesSource>}
     */
    public function dataTable(Request $request): array
    {
        $query = SalesSource::query();
        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $recordsFiltered = (clone $query)->count();
        $orderableColumns = ['id', 'name', 'is_active', 'sort_order'];
        $orderColumn = $orderableColumns[$request->integer('order.0.column', 0)] ?? 'id';
        $orderDirection = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderColumn, $orderDirection);

        $length = min(max($request->integer('length', 10), 1), 100);
        $start = max($request->integer('start', 0), 0);

        return [
            'draw' => $request->integer('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $query->skip($start)->take($length)->get(),
        ];
    }

    /** @return Collection<int, SalesSource> */
    public function activeOptions(): Collection
    {
        return SalesSource::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(array $data): SalesSource
    {
        return SalesSource::query()->create($data);
    }

    public function find(int|string $id): SalesSource
    {
        return SalesSource::query()->findOrFail($id);
    }

    public function update(SalesSource $salesSource, array $data): SalesSource
    {
        $salesSource->update($data);

        return $salesSource->refresh();
    }
}
