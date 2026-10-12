<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterData\StoreSalesSourceRequest;
use App\Http\Requests\MasterData\UpdateSalesSourceRequest;
use App\Models\SalesSource;
use App\Services\MasterData\SalesSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesSourceController extends Controller
{
    public function __construct(private readonly SalesSourceService $salesSources) {}

    public function showTableList(): View
    {
        return view('sales_sources.index');
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->salesSources->dataTable($request));
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Provide sales source data to create.']);
    }

    public function activeOptions(): JsonResponse
    {
        return response()->json($this->salesSources->activeOptions());
    }

    public function store(StoreSalesSourceRequest $request): JsonResponse
    {
        $salesSource = $this->salesSources->create($request->validated());

        return response()->json($salesSource, 201);
    }

    public function show(SalesSource $salesSource): JsonResponse
    {
        return response()->json($salesSource);
    }

    public function edit(SalesSource $salesSource): JsonResponse
    {
        return response()->json($salesSource);
    }

    public function update(UpdateSalesSourceRequest $request, SalesSource $salesSource): JsonResponse
    {
        $salesSource = $this->salesSources->update($salesSource, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sales source updated successfully.',
            'data' => $salesSource,
        ]);
    }
}
