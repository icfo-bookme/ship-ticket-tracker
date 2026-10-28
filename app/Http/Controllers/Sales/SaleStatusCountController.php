<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\SaleStatusCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleStatusCountController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SaleStatusCountService $counts): JsonResponse
    {
        return response()->json((object) $counts->counts($request->user()))
            ->header('Cache-Control', 'private, no-store');
    }
}
