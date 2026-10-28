<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesDataTableService
{
    public function __construct(private readonly SaleVisibilityService $visibility) {}

    /**
     * @return array{sale_ids: array<int, int>, exceeds_limit: bool}
     */
    public function eligibleShippedSaleIds(Request $request): array
    {
        $query = ShipTicketSale::query()
            ->where('status', SaleStatus::ShipmentIdEntered->value)
            ->whereHas('printedTickets')
            ->whereHas('shipment', fn (Builder $shipment): Builder => $shipment->whereNotNull('shipment_id'));

        $this->visibility->excludeSalesWithActiveRefunds($query);
        $this->applyFilters($query, $request);
        $this->applySearch($query, (string) $request->input('search', ''));

        $saleIds = $query->orderBy('id')->limit(501)->pluck('id')->map(
            fn ($id): int => (int) $id
        )->all();

        return [
            'sale_ids' => array_slice($saleIds, 0, 500),
            'exceeds_limit' => count($saleIds) > 500,
        ];
    }

    public function response(Request $request, string $status): JsonResponse
    {
        $query = ShipTicketSale::with([
            'ships',
            'companies',
            'shipment',
            'categories',
            'payments',
            'refunds',
            'seller:id,name',
            'printedTickets',
            'groupedTickets.sale:id,status',
            'verifyby.verifiedByUser',
        ]);

        $this->visibility->applyStatusVisibility($query, $status);
        $this->visibility->excludeSalesWithActiveRefunds($query);

        $this->applyFilters($query, $request);
        $this->applySearch($query, (string) $request->input('search.value', ''));

        $filteredRecords = $query->count();
        $start = max(0, $request->integer('start', 0));
        $requestedLength = $request->integer('length', 10);
        $length = $requestedLength < 1 ? 10 : min($requestedLength, 100);

        $sortDirection = $request->input('sort') === 'oldest' ? 'asc' : 'desc';

        $sales = $query
            ->orderBy('created_at', $sortDirection)
            ->orderBy('id', $sortDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $sales->each(function (ShipTicketSale $sale): void {
            $sale->setAttribute('ticket_count', (int) $sale->categories->sum('quantity'));
            $sale->setAttribute('ticket_value', $sale->ticket_value);
            $sale->setAttribute('whatsapp_display', $sale->whatsapp ?: $sale->whatsapp_username);
            $settledAmount = (float) $sale->payments
                ->reject(fn ($payment): bool => $payment->is_bftn && $payment->settlement_status !== 'received')
                ->sum('received_amount');
            $sale->setAttribute('received_amount', $settledAmount);
            $sale->setAttribute(
                'bftn_status',
                $sale->payments->contains(fn ($payment): bool => $payment->is_bftn) ? 'yes' : 'no'
            );
            $sale->setAttribute(
                'bftn_received',
                $sale->payments->where('is_bftn', true)->isNotEmpty()
                    && $sale->payments->where('is_bftn', true)->every(fn ($payment): bool => $payment->settlement_status === 'received')
            );
        });

        $recordsTotalQuery = ShipTicketSale::query();
        $this->visibility->applyStatusVisibility($recordsTotalQuery, $status);
        $this->visibility->excludeSalesWithActiveRefunds($recordsTotalQuery);

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotalQuery->count(),
            'recordsFiltered' => $filteredRecords,
            'data' => $sales,
        ]);
    }

    public function deletedResponse(Request $request): JsonResponse
    {
        $query = ShipTicketSale::onlyTrashed()
            ->with(['ships:id,name', 'companies:id,name']);
        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function (Builder $sales) use ($search): void {
                $sales->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_mobile', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('ships', fn (Builder $ships) => $ships->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('companies', fn (Builder $companies) => $companies->where('name', 'like', "%{$search}%"));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $start = max(0, $request->integer('start', 0));
        $requestedLength = $request->integer('length', 10);
        $length = $requestedLength < 1 ? 10 : min($requestedLength, 100);

        $sales = $query->orderByDesc('deleted_at')
            ->skip($start)
            ->take($length)
            ->get()
            ->map(fn (ShipTicketSale $sale): array => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_mobile' => $sale->customer_mobile,
                'ship_name' => $sale->ships?->name,
                'company_name' => $sale->companies?->name,
                'journey_date' => $sale->getRawOriginal('journey_date'),
                'status' => $sale->status,
                'deleted_at' => $sale->deleted_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
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
                ->orWhere('whatsapp', 'like', "%{$searchValue}%")
                ->orWhere('whatsapp_username', 'like', "%{$searchValue}%")
                ->orWhere('email', 'like', "%{$searchValue}%")
                ->orWhere('nid', 'like', "%{$searchValue}%")
                ->orWhere('sales_source', 'like', "%{$searchValue}%")
                ->orWhereRaw('(
                    SELECT COALESCE(SUM(categories.quantity * categories.unit_price), 0)
                    FROM categories
                    WHERE categories.ticket_id = ship_ticket_sales.id
                ) LIKE ?', ["%{$searchValue}%"])
                ->orWhere('discount_amount', 'like', "%{$searchValue}%")
                ->orWhere('total_payable', 'like', "%{$searchValue}%")
                ->orWhereHas('payments', fn ($payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                ->orWhereRaw('(
                    SELECT COALESCE(SUM(categories.quantity), 0)
                    FROM categories
                    WHERE categories.ticket_id = ship_ticket_sales.id
                ) LIKE ?', ["%{$searchValue}%"])
                ->orWhereHas('payments', fn ($payments) => $payments->where('received_amount', 'like', "%{$searchValue}%"))
                ->orWhereRaw('('.ShipTicketSale::dueAmountSql().') LIKE ?', ["%{$searchValue}%"])
                ->orWhere('sold_by', 'like', "%{$searchValue}%")
                ->orWhereHas('categories.package', fn ($packages) => $packages->where('name', 'like', "%{$searchValue}%"))
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
