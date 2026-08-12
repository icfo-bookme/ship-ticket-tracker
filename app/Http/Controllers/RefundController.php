<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function index()
    {
        $refunds = Refund::all();

        return response()->json($refunds);
    }

    public function refundableCS(Request $request)
    {
        $shipId = $request->input('ship_id');
        $companyId = $request->input('company_id');
        $journeyDate = $request->input('journey_date');

        // DataTables parameters
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $searchValue = $request->input('search.value', '');

        $query = ShipTicketSale::with([
            'ships',
            'companies',
        ])
            ->whereNotIn('status', ['pending', 'refunded', 'partial-refunded']);

        // Apply filters
        if ($shipId && ! empty($shipId)) {
            $query->where('ship_id', $shipId);
        }

        if ($companyId && ! empty($companyId)) {
            $query->where('company_id', $companyId);
        }

        if ($journeyDate && ! empty($journeyDate)) {
            $query->whereDate('journey_date', $journeyDate);
        }

        // Total records ignoring search (DataTables convention)
        $totalRecords = $query->count();

        // Apply search
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                // Direct table columns
                $q->where('customer_name', 'like', "%{$searchValue}%")
                    ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('nid', 'like', "%{$searchValue}%")
                    ->orWhere('sales_source', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                    ->orWhere('payment_method', 'like', "%{$searchValue}%")
                    ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('received_amount', 'like', "%{$searchValue}%")
                    ->orWhere('due_amount', 'like', "%{$searchValue}%")
                    ->orWhere('sold_by', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_category', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")

                    // Date fields (search by formatted date or raw value)
                    ->orWhereDate('journey_date', $searchValue)
                    ->orWhere('journey_date', 'like', "%{$searchValue}%")
                    ->orWhereDate('return_date', $searchValue)
                    ->orWhere('return_date', 'like', "%{$searchValue}%")
                    ->orWhereDate('issued_date', $searchValue)
                    ->orWhere('issued_date', 'like', "%{$searchValue}%")

                    // Related tables (ships)
                    ->orWhereHas('ships', function ($shipQuery) use ($searchValue) {
                        $shipQuery->where('name', 'like', "%{$searchValue}%");
                    })

                    // Related tables (companies)
                    ->orWhereHas('companies', function ($companyQuery) use ($searchValue) {
                        $companyQuery->where('name', 'like', "%{$searchValue}%");
                    })

                    // Search by ID
                    ->orWhere('id', $searchValue);
            });
        }

        $recordsFiltered = $query->count();

        $sales = $query->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $sales,
        ]);
    }

    public function create()
    {
        return view('refund.componentItem');
    }

    public function refunded(Request $request)
    {
        try {
            $shipId = $request->input('ship_id');
            $companyId = $request->input('company_id');
            $journeyDate = $request->input('journey_date');

            // DataTables parameters
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $searchValue = $request->input('search.value', '');

            $query = ShipTicketSale::with(['ships', 'companies', 'refund'])
                ->whereIn('status', ['refunded', 'partial-refunded']);

            if (! empty($shipId)) {
                $query->where('ship_id', $shipId);
            }

            if (! empty($companyId)) {
                $query->where('company_id', $companyId);
            }

            if (! empty($journeyDate)) {
                $query->whereDate('journey_date', $journeyDate);
            }

            if (! empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('customer_name', 'like', "%{$searchValue}%")
                        ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhere('nid', 'like', "%{$searchValue}%")
                        ->orWhere('sales_source', 'like', "%{$searchValue}%")
                        ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                        ->orWhere('payment_method', 'like', "%{$searchValue}%")
                        ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                        ->orWhere('received_amount', 'like', "%{$searchValue}%")
                        ->orWhere('due_amount', 'like', "%{$searchValue}%")
                        ->orWhere('sold_by', 'like', "%{$searchValue}%")
                        ->orWhere('ticket_category', 'like', "%{$searchValue}%")
                        ->orWhere('status', 'like', "%{$searchValue}%")
                        ->orWhereDate('journey_date', $searchValue)
                        ->orWhere('journey_date', 'like', "%{$searchValue}%")
                        ->orWhereDate('return_date', $searchValue)
                        ->orWhere('return_date', 'like', "%{$searchValue}%")
                        ->orWhereDate('issued_date', $searchValue)
                        ->orWhere('issued_date', 'like', "%{$searchValue}%")
                        ->orWhereHas('ships', function ($shipQuery) use ($searchValue) {
                            $shipQuery->where('name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('companies', function ($companyQuery) use ($searchValue) {
                            $companyQuery->where('name', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('id', $searchValue);
                });
            }

            $totalRecords = $query->count();

            $sales = $query->skip($start)
                ->take($length)
                ->get();

            // Calculate totals
            $totalRefundedTickets = 0;
            $totalRefundedAmount = 0;
            foreach ($sales as $sale) {
                if ($sale->refund) {
                    $totalRefundedTickets += (int) $sale->refund->refunded_number_of_tickets;
                    $totalRefundedAmount += (float) $sale->refund->refunded_amount;
                }
            }

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $sales,
                'total_refunded_tickets' => $totalRefundedTickets,
                'total_refunded_amount' => $totalRefundedAmount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while retrieving refund data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_id' => 'required|integer',
            'refunded_number_of_tickets' => 'required|integer|min:1',
            'refunded_amount' => 'required|numeric|min:0',
        ]);

        $refund = Refund::create($validated);

        return response()->json($refund, 201);
    }

    public function showRefundedCS()
    {
        return view('refunded.index');
    }

    public function fullRefunds(Request $request)
    {

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $id) {
                $sale = ShipTicketSale::find($id);
                if ($sale && $sale->status !== 'pending') {
                    Refund::create([
                        'sales_id' => $sale->id,
                        'refunded_number_of_tickets' => $sale->number_of_ticket,
                        'refunded_amount' => $sale->received_amount,
                    ]);

                    $sale->status = 'refunded';
                    $sale->save();
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Refund processed successfully.']);
    }

    public function partialRefund(Request $request, $id)
    {
        $validated = $request->validate([
            'refunded_number_of_tickets' => 'required|integer|min:1',
            'refunded_amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string|max:255',
        ]);

        $sale = ShipTicketSale::find($id);
        abort_unless($sale, 404, 'Sale not found.');

        DB::transaction(function () use ($sale, $validated) {
            Refund::create([
                'sales_id' => $sale->id,
                'refunded_number_of_tickets' => $validated['refunded_number_of_tickets'],
                'refunded_amount' => $validated['refunded_amount'],
                'remark' => $validated['remark'] ?? null,
            ]);

            $sale->status = ($sale->number_of_ticket == $validated['refunded_number_of_tickets'])
                ? 'refunded'
                : 'partial-refunded';
            $sale->save();
        });

        return response()->json(['success' => true, 'message' => 'Refund processed successfully.']);
    }

    public function show($id)
    {
        $refund = Refund::find($id);

        if (! $refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        return response()->json($refund);
    }

    public function edit($id)
    {
        // Not necessary for APIs, usually handled in web apps
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'refunded_number_of_tickets' => 'required|integer|min:1',
            'refunded_amount' => 'required|numeric|min:0',
        ]);

        $refund = Refund::find($id);

        if (! $refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $sale = ShipTicketSale::find($refund->sales_id);

        if (! $sale) {
            return response()->json(['message' => 'Associated sale not found'], 404);
        }

        $refund->update([
            'refunded_number_of_tickets' => $request->refunded_number_of_tickets,
            'refunded_amount' => $request->refunded_amount,
        ]);

        $sale->status = ($sale->number_of_ticket == $request->refunded_number_of_tickets)
            ? 'refunded'
            : 'partial-refunded';
        $sale->save();

        return response()->json(['success' => true, 'message' => 'Refund updated successfully.']);
    }

    // Remove the specified refund from storage
    public function destroy($id)
    {
        $refund = Refund::find($id);

        if (! $refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $refund->delete();

        return response()->json(['message' => 'Refund deleted successfully']);
    }
}
