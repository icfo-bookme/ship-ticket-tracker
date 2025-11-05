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
        $companies = Company::all();
        return response()->json($companies);  
    }

    public function create()
    {
        return response()->json(['message' => 'Provide ship data to create'], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string',
        ]);

        // Create a new company
        $company = new Company($validated);
        $company->save();

        return response()->json($company, 201); 
    }

    public function show(string $id)
    {
        //
    }

   
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required',
        ]);

        $company = Company::find($id);

        $company->update($request->all());

        return response()->json([
            'success' => true,
            'message' => ' updated successfully',
        ], 200);  
    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {   
        $company = Company::find($id);
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ship deleted successfully',
        ], 200);
    }
}
