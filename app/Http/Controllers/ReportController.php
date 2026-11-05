<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesReportRequest;
use App\Http\Requests\TicketIssuedReportRequest;
use App\Models\Company;
use App\Models\Ship;
use App\Services\Finance\ExtraPaymentService;
use App\Services\Reports\BftnReportService;
use App\Services\Reports\RefundReportService;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\TicketIssuedReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $salesReports,
        private readonly ExtraPaymentService $extraPayments,
        private readonly RefundReportService $refundReports,
        private readonly BftnReportService $bftnReports,
        private readonly TicketIssuedReportService $ticketIssuedReports,
    ) {}

    public function index()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('reports.index', compact('ships', 'companies'));
    }

    public function reports(SalesReportRequest $request): JsonResponse
    {
        try {
            return response()->json($this->salesReports->dataTable($request), 200);
        } catch (\Throwable $e) {
            Log::error('Report generation error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json($this->salesReports->emptyResponse($request), 500);
        }
    }

    public function ticketIssued(): View
    {
        return view('reports.ticket-issued', [
            'ships' => Ship::query()->orderBy('name')->get(['id', 'name']),
            'today' => now('Asia/Dhaka')->toDateString(),
        ]);
    }

    public function ticketIssuedReports(TicketIssuedReportRequest $request): JsonResponse
    {
        return response()->json($this->ticketIssuedReports->dataTable($request->validated()));
    }

    public function refunds()
    {
        return view('reports.refunds', [
            'ships' => Ship::query()->orderBy('name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
        ]);
    }

    public function refundReports(Request $request): JsonResponse
    {
        return response()->json($this->refundReports->dataTable($request));
    }

    public function bftn()
    {
        return view('reports.bftn', [
            'ships' => Ship::query()->orderBy('name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
        ]);
    }

    public function bftnReports(Request $request): JsonResponse
    {
        return response()->json($this->bftnReports->dataTable($request));
    }

    public function extraReceived()
    {
        return view('reports.extra-received');
    }

    public function extraReceivedData(Request $request): JsonResponse
    {
        return response()->json($this->extraPayments->dataTable($request));
    }

    public function adjustExtraToOtherFee(int $id): JsonResponse
    {
        $result = $this->extraPayments->adjustToOtherFee($id);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'This sale has no extra received amount to adjust.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Extra amount of {$result['amount']} has been adjusted to Other Fee.",
        ]);
    }

    public function refundExtra(int $id): JsonResponse
    {
        $result = $this->extraPayments->requestRefund($id);

        if ($result['refund'] === null) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Refund request of {$result['refund']->customer_refund_amount} has been created.",
        ]);
    }
}
