<?php

namespace App\Services\Finance;

use App\Enums\SaleStatus;
use App\Models\CashCollection;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashCollectionService
{
    /**
     * @return array{availableCashAmount: float, totalReceivedAmount: float, totalRefundedAmount: float, totalCashedOutAmount: float}
     */
    public function summary(): array
    {
        $totalReceivedAmount = (float) ShipTicketSale::where('status', '!=', SaleStatus::Pending->value)
            ->sum('received_amount');

        $totalRefundedAmount = (float) Refund::query()
            ->where('status', 'completed')
            ->whereHas('sale', fn ($sales) => $sales->where('status', '!=', SaleStatus::Pending->value))
            ->sum('customer_refund_amount');

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
        $cashoutAmount = (float) $data['cashout_amount'];

        if ($cashoutAmount > $this->availableCashAmount()) {
            throw ValidationException::withMessages([
                'cashout_amount' => 'Cash withdrawal cannot exceed available cash.',
            ]);
        }

        return CashCollection::create([
            'name' => $data['name'] ?? null,
            'entry_by' => auth()->id(),
            'cashout_amount' => $cashoutAmount,
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
        $cashoutAmount = (float) $data['cashout_amount'];
        $availableCashForUpdate = $this->availableCashAmount() + (float) $collection->cashout_amount;

        if ($cashoutAmount > $availableCashForUpdate) {
            throw ValidationException::withMessages([
                'cashout_amount' => 'Cash withdrawal cannot exceed available cash.',
            ]);
        }

        $collection->update([
            'name' => $data['name'] ?? null,
            'entry_by' => $collection->entry_by ?? auth()->id(),
            'cashout_amount' => $cashoutAmount,
        ]);

        return $collection;
    }

    private function availableCashAmount(): float
    {
        $totalReceivedAmount = (float) ShipTicketSale::where('status', '!=', SaleStatus::Pending->value)
            ->sum('received_amount');
        $totalRefundedAmount = (float) Refund::query()
            ->where('status', 'completed')
            ->whereHas('sale', fn ($sales) => $sales->where('status', '!=', SaleStatus::Pending->value))
            ->sum('customer_refund_amount');
        $totalCashedOutAmount = (float) CashCollection::sum('cashout_amount');

        return $totalReceivedAmount - $totalRefundedAmount - $totalCashedOutAmount;
    }
}
