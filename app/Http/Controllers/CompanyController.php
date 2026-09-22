<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterData\StoreCompanyRequest;
use App\Http\Requests\MasterData\UpdateCompanyRequest;
use App\Services\MasterData\CompanyService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companies) {}

    public function showTableList()
    {
        return view('companies.componentItem');
    }

    public function index(Request $request)
    {
        return response()->json($this->companies->dataTable($request));
    }

    public function create()
    {
        return response()->json(['message' => 'Provide company data to create.'], 200);
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = $this->companies->create($request->validated());

        return response()->json($company, 201);
    }

    public function show(string $id)
    {
        return response()->json($this->companies->find($id));
    }

    public function edit(string $id)
    {
        return response()->json($this->companies->find($id));
    }

    public function update(UpdateCompanyRequest $request, string $id)
    {
        $this->companies->update($this->companies->find($id), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully.',
        ], 200);
    }

    public function destroy(string $id)
    {
        $company = $this->companies->find($id);
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully.',
        ], 200);
    }
}
