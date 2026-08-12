<?php

namespace App\Http\Controllers;

use App\Models\ExcelSetting;
use Illuminate\Http\Request;

class ExcelSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = ExcelSetting::all();

        return response()->json($settings);
    }

    public function showTableList()
    {
        return view('Excel.componentItem');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'spreadsheetId' => 'required|string|max:255',
            'range' => 'required|string|max:255',
        ]);

        $setting = ExcelSetting::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Excel setting created successfully',
            'data' => $setting,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ExcelSetting $excelSetting)
    {
        return response()->json([
            'success' => true,
            'data' => $excelSetting,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExcelSetting $excelSetting)
    {
        $validated = $request->validate([
            'spreadsheetId' => 'required|string|max:255',
            'range' => 'required|string|max:255',
        ]);

        $excelSetting->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Excel setting updated successfully',
            'data' => $excelSetting,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExcelSetting $excelSetting)
    {
        $excelSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Excel setting deleted successfully',
        ], 200);
    }
}
