<?php

namespace App\Services\MasterData;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyService
{
    public function dataTable(Request $request): array
    {
        $query = Company::query();
        $total = (clone $query)->count();
        $search = $request->input('search.value');
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        $filtered = (clone $query)->count();
        $orderColumn = ['id', 'name', 'status'][$request->integer('order.0.column', 0)] ?? 'id';
        $query->orderBy($orderColumn, $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc');
        $length = min(max($request->integer('length', 10), 1), 100);
        $items = $query->skip($request->integer('start', 0))->take($length)->get();

        return ['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $items];
    }

    public function create(array $data): Company
    {
        return Company::create($data);
    }

    public function find(string $id): Company
    {
        $company = Company::find($id);
        abort_unless($company, 404, 'Company not found.');

        return $company;
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }
}
