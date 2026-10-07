<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\PrintedTicket;
use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesDataTableService
{
    public function response(Request $request, string $status): JsonResponse
    {
        $query = ShipTicketSale::with([
            'ships',
            'companies',
            'shipment',
            'payments',
            'refunds',
            'seller:id,name',
            'bftn',
            'printedTickets',
            'groupedTickets.sale:id,status',
            'verifyby.verifiedByUser',
        ]);

        $this->applyStatusVisibility($query, $status);
        $this->excludeSalesWithActiveRefunds($query);

        $this->applyFilters($query, $request);
        $this->applySearch($query, (string) $request->input('search.value', ''));

        $filteredRecords = $query->count();
        $start = max(0, $request->integer('start', 0));
        $requestedLength = $request->integer('length', 10);
        $length = $requestedLength < 1 ? 10 : min($requestedLength, 100);

        $sales = $query
            ->skip($start)
            ->take($length)
            ->get();

        $sales->each(function (ShipTicketSale $sale): void {
            $sale->setAttribute('whatsapp_display', $sale->whatsapp ?: $sale->whatsapp_username);
            $sale->setAttribute(
                'bftn_received',
                $sale->bftn_status === 'yes' && (bool) $sale->bftn?->received_status
            );
        });

        $recordsTotalQuery = ShipTicketSale::query();
        $this->applyStatusVisibility($recordsTotalQuery, $status);
        $this->excludeSalesWithActiveRefunds($recordsTotalQuery);

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotalQuery->count(),
            'recordsFiltered' => $filteredRecords,
            'data' => $sales,
        ]);
    }

    private function applyStatusVisibility(Builder $query, string $status): void
    {
        $groupStatuses = [
            SaleStatus::TicketIssued->value,
            SaleStatus::TicketPrinted->value,
            SaleStatus::ShipmentIdEntered->value,
            SaleStatus::Shipped->value,
            SaleStatus::CollectFromOffice->value,
        ];

        if (! in_array($status, $groupStatuses, true)) {
            $query->where('status', $status);

            return;
        }

        $groupsWithStatusSales = PrintedTicket::query()
            ->select('group_by_id')
            ->whereNotNull('group_by_id')
            ->whereIn('sales_id', ShipTicketSale::query()
                ->select('id')
                ->where('status', $status))
            ->distinct();

        $saleIdsInVisibleGroups = PrintedTicket::query()
            ->select('sales_id')
            ->whereIn('group_by_id', $groupsWithStatusSales);

        $query->where(function (Builder $visibleSales) use ($status, $saleIdsInVisibleGroups): void {
            $visibleSales->where('status', $status)
                ->orWhereIn('id', $saleIdsInVisibleGroups);
        });
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

    private function excludeSalesWithActiveRefunds(Builder $query): void
    {
        $query->whereRaw('(
            SELECT COALESCE(SUM(refunded_number_of_tickets), 0)
            FROM refunds
            WHERE refunds.sales_id = ship_ticket_sales.id
                AND refunds.status != ?
        ) < ship_ticket_sales.number_of_ticket', ['cancelled']);
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
                ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                ->orWhere('discount_amount', 'like', "%{$searchValue}%")
                ->orWhere('total_payable', 'like', "%{$searchValue}%")
                ->orWhereHas('payments', fn ($payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                ->orWhere('received_amount', 'like', "%{$searchValue}%")
                ->orWhere('due_amount', 'like', "%{$searchValue}%")
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
