<?php

namespace App\Services\Reports;

use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BftnReportService
{
    /** @return array<string, mixed> */
    public function dataTable(Request $request): array
    {
        $query = $this->filteredQuery($request);
        $recordsTotal = (clone $query)->count();
        $this->applySearch($query, trim((string) $request->input('search.value', '')));
        $recordsFiltered = (clone $query)->count();
        $totals = $this->totals(clone $query);
        $sales = $query->with(['ships', 'companies', 'bftn'])
            ->orderByDesc('ship_ticket_sales.id')
            ->skip(max((int) $request->input('start', 0), 0))
            ->take(min(max((int) $request->input('length', 10), 1), 100))
            ->get();

        return [
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $sales->map(fn (ShipTicketSale $sale): array => $this->formatSale($sale)),
            'totals' => $totals,
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return ShipTicketSale::query()
            ->where('bftn_status', 'yes')
            ->when($request->filled('ship_id'), fn (Builder $query) => $query->where('ship_id', $request->input('ship_id')))
            ->when($request->filled('company_id'), fn (Builder $query) => $query->where('company_id', $request->input('company_id')))
            ->when($request->input('status') === 'received', fn (Builder $query) => $query->whereHas('bftn', fn (Builder $bftn) => $bftn->where('received_status', true)))
            ->when($request->input('status') === 'pending', fn (Builder $query) => $query->whereDoesntHave('bftn', fn (Builder $bftn) => $bftn->where('received_status', true)))
            ->when($request->filled('start_date'), fn (Builder $query) => $query->whereHas('bftn', fn (Builder $bftn) => $bftn->whereDate('bftn_date_time', '>=', $request->input('start_date'))))
            ->when($request->filled('end_date'), fn (Builder $query) => $query->whereHas('bftn', fn (Builder $bftn) => $bftn->whereDate('bftn_date_time', '<=', $request->input('end_date'))))
            ->when($request->filled('received_start_date'), fn (Builder $query) => $query->whereHas('bftn', fn (Builder $bftn) => $bftn->whereDate('received_at', '>=', $request->input('received_start_date'))))
            ->when($request->filled('received_end_date'), fn (Builder $query) => $query->whereHas('bftn', fn (Builder $bftn) => $bftn->whereDate('received_at', '<=', $request->input('received_end_date'))));
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('ship_ticket_sales.id', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_mobile', 'like', "%{$search}%")
                ->orWhereHas('ships', fn (Builder $ships) => $ships->where('name', 'like', "%{$search}%"))
                ->orWhereHas('companies', fn (Builder $companies) => $companies->where('name', 'like', "%{$search}%"));
        });
    }

    /** @return array<string, int|string> */
    private function totals(Builder $query): array
    {
        $totals = (clone $query)->selectRaw('
            COUNT(*) AS total_count,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id AND bftn.received_status = 1) THEN 1 ELSE 0 END) AS received_count,
            SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id AND bftn.received_status = 1) THEN 1 ELSE 0 END) AS pending_count,
            COALESCE(SUM(ship_ticket_sales.received_amount), 0) AS total_amount,
            COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id AND bftn.received_status = 1) THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS received_amount,
            COALESCE(SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM bftn WHERE bftn.sales_id = ship_ticket_sales.id AND bftn.received_status = 1) THEN ship_ticket_sales.received_amount ELSE 0 END), 0) AS pending_amount
        ')->first();

        return [
            'total_count' => (int) $totals->total_count,
            'received_count' => (int) $totals->received_count,
            'pending_count' => (int) $totals->pending_count,
            'total_amount' => number_format((float) $totals->total_amount, 2, '.', ''),
            'received_amount' => number_format((float) $totals->received_amount, 2, '.', ''),
            'pending_amount' => number_format((float) $totals->pending_amount, 2, '.', ''),
        ];
    }

    /** @return array<string, mixed> */
    private function formatSale(ShipTicketSale $sale): array
    {
        return [
            'id' => $sale->id,
            'customer_name' => $sale->customer_name,
            'customer_mobile' => $sale->customer_mobile,
            'ship_name' => $sale->ships?->name,
            'company_name' => $sale->companies?->name,
            'amount' => number_format((float) $sale->received_amount, 2, '.', ''),
            'bftn_date_time' => $sale->bftn?->bftn_date_time,
            'received_status' => (bool) $sale->bftn?->received_status,
            'received_at' => $sale->bftn?->received_at?->toDateTimeString(),
        ];
    }
}
