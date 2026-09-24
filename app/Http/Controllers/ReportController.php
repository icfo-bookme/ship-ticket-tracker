<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Ship;
use App\Services\Reports\SalesReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct(private readonly SalesReportService $salesReports) {}

    public function index()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('reports.index', compact('ships', 'companies'));
    }

    public function reports(Request $request): JsonResponse
    {
        try {
            return response()->json($this->salesReports->dataTable($request), 200);
        } catch (\Throwable $e) {
            Log::error('Report generation error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json($this->salesReports->emptyResponse($request), 500);
        }
    }
}
