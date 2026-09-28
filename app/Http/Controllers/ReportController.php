<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Services\Reports\SalesReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function extraReceived()
    {
        return view('reports.extra-received');
    }

    public function extraReceivedData(Request $request): JsonResponse
    {
        $query = ShipTicketSale::with(['ships', 'companies'])
            ->whereColumn('received_amount', '>', 'total_payable');

        $total = (clone $query)->count();
        $sales = $query->latest('id')
            ->skip((int) $request->input('start', 0))
            ->take(min(100, max(1, (int) $request->input('length', 10))))
            ->get();

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $sales->map(fn (ShipTicketSale $sale): array => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_mobile' => $sale->customer_mobile,
                'ship_name' => $sale->ships?->name,
                'company_name' => $sale->companies?->name,
                'journey_date' => $sale->journey_date,
                'total_payable' => (float) $sale->total_payable,
                'received_amount' => (float) $sale->received_amount,
                'extra_received_amount' => (float) $sale->received_amount - (float) $sale->total_payable,
            ]),
        ]);
    }

    public function adjustExtraToOtherFee(int $id): JsonResponse
    {
        /** @var array{sale: ShipTicketSale|null, extra: float} $result */
        $result = DB::transaction(function () use ($id): array {
            $sale = ShipTicketSale::query()->lockForUpdate()->findOrFail($id);
            $extra = (float) $sale->received_amount - (float) $sale->total_payable;

            if ($extra <= 0) {
                return ['sale' => null, 'extra' => 0.0];
            }

            $sale->update([
                'other_fee' => (float) $sale->other_fee + $extra,
                'total_payable' => (float) $sale->total_payable + $extra,
                'due_amount' => 0,
            ]);

            return ['sale' => $sale, 'extra' => $extra];
        });

        if ($result['sale'] === null) {
            return response()->json([
                'success' => false,
                'message' => 'This sale has no extra received amount to adjust.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Extra amount of {$result['extra']} has been adjusted to Other Fee.",
        ]);
    }

    public function refundExtra(int $id): JsonResponse
    {
        $refund = DB::transaction(function () use ($id): Refund {
            $sale = ShipTicketSale::query()->lockForUpdate()->findOrFail($id);
            $extra = (float) $sale->received_amount - (float) $sale->total_payable;

            if ($extra <= 0) {
                abort(422, 'This sale has no extra received amount to refund.');
            }

            if ($sale->refunds()->where('refund_type', 'extra_payment')->whereNotIn('status', ['cancelled'])->exists()) {
                abort(422, 'A refund request already exists for this extra amount.');
            }

            return Refund::create([
                'sales_id' => $sale->id,
                'refund_type' => 'extra_payment',
                'reason' => 'Extra received amount',
                'refunded_number_of_tickets' => 0,
                'refunded_amount' => $extra,
                'gross_refund_amount' => $extra,
                'customer_refund_amount' => $extra,
                'status' => 'requested',
                'requested_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Refund request of {$refund->customer_refund_amount} has been created.",
        ]);
    }
}
