<?php

namespace App\Services\Settings;

use App\Models\ExcelSetting;
use Illuminate\Http\Request;

class ExcelSettingService
{
    public function dataTable(Request $request): array
    {
        $query = ExcelSetting::query();
        $total = (clone $query)->count();
        if ($search = $request->input('search.value')) {
            $query->where('name', 'like', "%{$search}%");
        }
        $filtered = (clone $query)->count();
        $length = min(max($request->integer('length', 10), 1), 100);

        return ['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $query->latest()->skip($request->integer('start', 0))->take($length)->get()];
    }

    public function create(array $data): ExcelSetting
    {
        return ExcelSetting::create($data);
    }

    public function update(ExcelSetting $excelSetting, array $data): ExcelSetting
    {
        $excelSetting->update($data);

        return $excelSetting;
    }
}
