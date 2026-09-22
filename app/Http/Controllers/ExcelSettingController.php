<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\SaveExcelSettingRequest;
use App\Models\ExcelSetting;
use App\Services\Settings\ExcelSettingService;
use Illuminate\Http\Request;

class ExcelSettingController extends Controller
{
    public function __construct(private readonly ExcelSettingService $excelSettings) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json($this->excelSettings->dataTable($request));
    }

    public function showTableList()
    {
        return view('Excel.componentItem');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveExcelSettingRequest $request)
    {
        $setting = $this->excelSettings->create($request->validated());

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
    public function update(SaveExcelSettingRequest $request, ExcelSetting $excelSetting)
    {
        $excelSetting = $this->excelSettings->update($excelSetting, $request->validated());

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
