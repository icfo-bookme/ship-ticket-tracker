<?php

namespace App\Services\Sales;

use App\Models\ShipTicketSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesDataTableService
{
    public function response(Request $request, string $status): JsonResponse
    {
        $query = ShipTicketSale::with([
            'ships.packages',
            'categories',
            'companies',
            'coPassengers',
            'shipment',
            'payments',
            'PrintStatus',
            'printedTickets',
            'groupedTickets',
            'verifyby.verifiedByUser',
        ])
            ->withCount('printedTickets')
            ->where('status', $status);

        $this->applyFilters($query, $request);
        $this->applySearch($query, (string) $request->input('search.value', ''));

        $filteredRecords = $query->count();

        $sales = $query
            ->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 10))
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => ShipTicketSale::where('status', $status)->count(),
            'recordsFiltered' => $filteredRecords,
            'data' => $sales,
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('ship_id')) {
            $query->where('ship_id', $request->input('ship_id'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->filled('journey_date')) {
            $query->whereDate('journey_date', $request->input('journey_date'));
        }
    }

    private function applySearch($query, string $searchValue): void
    {
        if ($searchValue === '') {
            return;
        }

        $query->where(function ($q) use ($searchValue): void {
            $q->where('customer_name', 'like', "%{$searchValue}%")
                ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                ->orWhere('email', 'like', "%{$searchValue}%")
                ->orWhere('nid', 'like', "%{$searchValue}%")
                ->orWhere('sales_source', 'like', "%{$searchValue}%")
                ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                ->orWhere('discount_amount', 'like', "%{$searchValue}%")
                ->orWhere('payment_method', 'like', "%{$searchValue}%")
                ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                ->orWhere('received_amount', 'like', "%{$searchValue}%")
                ->orWhere('due_amount', 'like', "%{$searchValue}%")
                ->orWhere('sold_by', 'like', "%{$searchValue}%")
                ->orWhere('ticket_category', 'like', "%{$searchValue}%")
                ->orWhere('status', 'like', "%{$searchValue}%")
                ->orWhereDate('journey_date', $searchValue)
                ->orWhereDate('return_date', $searchValue)
                ->orWhereDate('issued_date', $searchValue)
                ->orWhereHas('ships', function ($shipQuery) use ($searchValue): void {
                    $shipQuery->where('name', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('companies', function ($companyQuery) use ($searchValue): void {
                    $companyQuery->where('name', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('shipment', function ($shipmentQuery) use ($searchValue): void {
                    $shipmentQuery->where('shipment_id', 'like', "%{$searchValue}%");
                })
                ->orWhere('id', $searchValue);
        });
    }
}
