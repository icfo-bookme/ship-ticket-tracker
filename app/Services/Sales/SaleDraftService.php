<?php

namespace App\Services\Sales;

use App\Models\SaleDraft;
use App\Models\ShipPackage;
use Illuminate\Http\Request;

class SaleDraftService
{
    public function dataTable(Request $request): array
    {
        $query = SaleDraft::query()->with(['ship:id,name', 'categories']);
        $total = (clone $query)->count();

        if ($request->filled('ship_id')) {
            $query->where('ship_id', $request->input('ship_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($categories) => $categories->where('ship_package_id', $request->input('category_id')));
        }

        foreach (['departure_date', 'return_date'] as $dateField) {
            if ($request->filled($dateField)) {
                $query->whereDate($dateField, $request->input($dateField));
            }
        }

        if ($search = $request->input('search.value')) {
            $query->where(function ($drafts) use ($search): void {
                $drafts->where('details', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('ship', fn ($ships) => $ships->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('categories', fn ($categories) => $categories->where('category_name', 'like', "%{$search}%"));
            });
        }

        $filtered = (clone $query)->count();
        $length = min(max($request->integer('length', 10), 1), 100);

        return [
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $query->latest()->skip($request->integer('start', 0))->take($length)->get(),
        ];
    }

    public function create(array $data, int $userId): SaleDraft
    {
        $categories = $data['ticket_categories'] ?? [];
        unset($data['ticket_categories']);

        return SaleDraft::query()->getConnection()->transaction(function () use ($data, $categories, $userId): SaleDraft {
            $draft = SaleDraft::create([...$data, 'created_by' => $userId]);
            $this->syncCategories($draft, $categories);

            return $draft->load(['ship:id,name', 'categories']);
        });
    }

    public function findForUser(SaleDraft $draft, int $userId): SaleDraft
    {
        abort_unless($draft->created_by === $userId, 404);

        return $draft->load(['ship:id,name', 'categories']);
    }

    public function update(SaleDraft $draft, array $data): SaleDraft
    {
        $categories = $data['ticket_categories'] ?? [];
        unset($data['ticket_categories']);

        return $draft->getConnection()->transaction(function () use ($draft, $data, $categories): SaleDraft {
            $draft->update($data);
            $this->syncCategories($draft, $categories);

            return $draft->refresh()->load(['ship:id,name', 'categories']);
        });
    }

    public function delete(SaleDraft $draft): void
    {
        $draft->delete();
    }

    private function syncCategories(SaleDraft $draft, array $categories): void
    {
        $packageIds = collect($categories)
            ->flatten(1)
            ->pluck('package_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $packages = ShipPackage::query()
            ->where('ship_id', $draft->ship_id)
            ->whereIn('id', $packageIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = [];
        foreach (['departure', 'return'] as $journeyType) {
            foreach ($categories[$journeyType] ?? [] as $category) {
                $quantity = (int) $category['quantity'];
                if ($quantity < 1) {
                    continue;
                }

                $package = $packages->get((int) $category['package_id']);
                $rows[] = [
                    'ship_package_id' => $package?->id,
                    'category_name' => $package?->name ?? $category['name'],
                    'journey_type' => $journeyType,
                    'quantity' => $quantity,
                ];
            }
        }

        $draft->categories()->delete();
        if ($rows !== []) {
            $draft->categories()->createMany($rows);
        }
    }
}
