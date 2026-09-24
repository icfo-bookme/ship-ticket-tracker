<?php

namespace App\Services\Finance;

use App\Enums\SaleStatus;
use App\Models\CashCollection;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;

class CashCollectionService
{
    /**
     * @return array{availableCashAmount: float, totalReceivedAmount: float, totalRefundedAmount: float, totalCashedOutAmount: float}
     */
    public function summary(): array
    {
        $totalReceivedAmount = (float) ShipTicketSale::where('status', '!=', SaleStatus::Pending->value)
            ->sum('received_amount');

        $totalRefundedAmount = (float) ShipTicketSale::query()
            ->leftJoin('refunds', 'refunds.sales_id', '=', 'ship_ticket_sales.id')
            ->where('ship_ticket_sales.status', '!=', SaleStatus::Pending->value)
            ->sum('refunds.refunded_amount');

        $totalCashedOutAmount = (float) CashCollection::sum('cashout_amount');
        $availableCashAmount = $totalReceivedAmount - $totalRefundedAmount - $totalCashedOutAmount;

        return compact(
            'availableCashAmount',
            'totalReceivedAmount',
            'totalRefundedAmount',
            'totalCashedOutAmount'
        );
    }

    public function create(array $data): CashCollection
    {
        return CashCollection::create([
            'name' => $data['name'] ?? null,
            'entry_by' => auth()->id(),
            'cashout_amount' => $data['cashout_amount'],
        ]);
    }

    public function dataTable(Request $request): array
    {
        $query = CashCollection::query();
        $total = (clone $query)->count();
        if ($search = $request->input('search.value')) {
            $query->where('name', 'like', "%{$search}%");
        }
        $filtered = (clone $query)->count();
        $length = min(max($request->integer('length', 10), 1), 100);

        return ['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $query->latest()->skip($request->integer('start', 0))->take($length)->get()];
    }

    public function update(CashCollection $collection, array $data): CashCollection
    {
        $collection->update([
            'name' => $data['name'] ?? null,
            'entry_by' => $collection->entry_by ?? auth()->id(),
            'cashout_amount' => $data['cashout_amount'],
        ]);

        return $collection;
    }
}
