<?php

namespace App\Services\MasterData;

use App\Models\WhatsappDetail;
use Illuminate\Http\Request;

class WhatsappDetailService
{
    public function dataTable(Request $request): array
    {
        $query = WhatsappDetail::query();
        $total = (clone $query)->count();
        if ($search = $request->input('search.value')) {
            $query->where('phone', 'like', "%{$search}%");
        }
        $filtered = (clone $query)->count();
        $length = min(max($request->integer('length', 10), 1), 100);

        return ['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $query->latest()->skip($request->integer('start', 0))->take($length)->get()];
    }

    public function create(array $data): WhatsappDetail
    {
        $whatsapp = new WhatsappDetail($data);
        $whatsapp->save();

        return $whatsapp;
    }
}
