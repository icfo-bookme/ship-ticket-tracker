<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function showTableList()
    {
        return view('companies.componentItem');
    }

    public function index()
    {
        return response()->json(Company::all());
    }

    public function create()
    {
        return response()->json(['message' => 'Provide company data to create.'], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string',
        ]);

        $company = Company::create($validated);

        return response()->json($company, 201);
    }

    public function show(string $id)
    {
        $company = Company::find($id);
        abort_unless($company, 404, 'Company not found.');

        return response()->json($company);
    }

    public function edit(string $id)
    {
        $company = Company::find($id);
        abort_unless($company, 404, 'Company not found.');

        return response()->json($company);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required',
        ]);

        $company = Company::find($id);
        abort_unless($company, 404, 'Company not found.');

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully.',
        ], 200);
    }

    public function destroy(string $id)
    {
        $company = Company::find($id);
        abort_unless($company, 404, 'Company not found.');

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully.',
        ], 200);
    }
}
