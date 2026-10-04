<?php

namespace App\Services\Sales;

use App\Models\SaleDraft;
use Illuminate\Http\Request;

class SaleDraftService
{
    public function dataTable(Request $request): array
    {
        $query = SaleDraft::query();
        $total = (clone $query)->count();

        foreach (['departure_date', 'return_date'] as $dateField) {
            if ($request->filled($dateField)) {
                $query->whereDate($dateField, $request->input($dateField));
            }
        }

        if ($search = $request->input('search.value')) {
            $query->where(function ($drafts) use ($search): void {
                $drafts->where('details', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
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
        return SaleDraft::create([...$data, 'created_by' => $userId]);
    }

    public function findForUser(SaleDraft $draft, int $userId): SaleDraft
    {
        abort_unless($draft->created_by === $userId, 404);

        return $draft;
    }

    public function update(SaleDraft $draft, array $data): SaleDraft
    {
        $draft->update($data);

        return $draft->refresh();
    }

    public function delete(SaleDraft $draft): void
    {
        $draft->delete();
    }
}
