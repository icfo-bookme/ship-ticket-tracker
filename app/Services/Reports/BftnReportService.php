<?php

namespace App\Services\Reports;

use App\Models\Payment;
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
        $payments = $query->with('sale.ships', 'sale.companies')
            ->orderByDesc('payments.id')
            ->skip(max((int) $request->input('start', 0), 0))
            ->take(min(max((int) $request->input('length', 10), 1), 100))
            ->get();

        return [
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $payments->map(fn (Payment $payment): array => $this->formatPayment($payment)),
            'totals' => $totals,
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return Payment::query()
            ->where('is_bftn', true)
            ->when($request->input('status') === 'received', fn (Builder $query) => $query->where('settlement_status', 'received'))
            ->when($request->input('status') === 'pending', fn (Builder $query) => $query->where('settlement_status', 'pending'))
            ->when($request->filled('ship_id'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale->where('ship_id', $request->input('ship_id'))))
            ->when($request->filled('company_id'), fn (Builder $query) => $query->whereHas('sale', fn (Builder $sale) => $sale->where('company_id', $request->input('company_id'))))
            ->when($request->filled('start_date'), fn (Builder $query) => $query->whereDate('bftn_issue_datetime', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn (Builder $query) => $query->whereDate('bftn_issue_datetime', '<=', $request->input('end_date')))
            ->when($request->filled('received_start_date'), fn (Builder $query) => $query->whereDate('received_at', '>=', $request->input('received_start_date')))
            ->when($request->filled('received_end_date'), fn (Builder $query) => $query->whereDate('received_at', '<=', $request->input('received_end_date')));
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->whereHas('sale', function (Builder $sale) use ($search): void {
            $sale->where('id', 'like', "%{$search}%")
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
            COALESCE(SUM(received_amount), 0) AS total_amount,
            COALESCE(SUM(CASE WHEN settlement_status = "pending" THEN 1 ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN settlement_status = "received" THEN 1 ELSE 0 END), 0) AS received_count,
            COALESCE(SUM(CASE WHEN settlement_status = "pending" THEN received_amount ELSE 0 END), 0) AS pending_amount,
            COALESCE(SUM(CASE WHEN settlement_status = "received" THEN received_amount ELSE 0 END), 0) AS received_amount
        ')->first();

        return [
            'total_count' => (int) $totals->total_count,
            'pending_count' => (int) $totals->pending_count,
            'received_count' => (int) $totals->received_count,
            'total_amount' => number_format((float) $totals->total_amount, 2, '.', ''),
            'pending_amount' => number_format((float) $totals->pending_amount, 2, '.', ''),
            'received_amount' => number_format((float) $totals->received_amount, 2, '.', ''),
        ];
    }

    /** @return array<string, mixed> */
    private function formatPayment(Payment $payment): array
    {
        $sale = $payment->sale;

        return [
            'id' => $payment->id,
            'sale_id' => $sale?->id,
            'customer_name' => $sale?->customer_name,
            'customer_mobile' => $sale?->customer_mobile,
            'ship_name' => $sale?->ships?->name,
            'company_name' => $sale?->companies?->name,
            'amount' => number_format((float) $payment->received_amount, 2, '.', ''),
            'bftn_date_time' => $payment->bftn_issue_datetime?->toDateTimeString(),
            'received_status' => $payment->settlement_status === 'received',
            'received_at' => $payment->received_at?->toDateTimeString(),
        ];
    }
}
