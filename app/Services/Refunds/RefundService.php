<?php

namespace App\Services\Refunds;

use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function create(array $data): Refund
    {
        return Refund::create($data);
    }

    public function fullRefund(array $saleIds): void
    {
        DB::transaction(function () use ($saleIds): void {
            foreach ($saleIds as $id) {
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
    }

    public function partialRefund(ShipTicketSale $sale, array $data): void
    {
        DB::transaction(function () use ($sale, $data): void {
            Refund::create([
                'sales_id' => $sale->id,
                'refunded_number_of_tickets' => $data['refunded_number_of_tickets'],
                'refunded_amount' => $data['refunded_amount'],
                'remark' => $data['remark'] ?? null,
            ]);

            $sale->status = ($sale->number_of_ticket == $data['refunded_number_of_tickets'])
                ? 'refunded'
                : 'partial-refunded';
            $sale->save();
        });
    }

    public function update(Refund $refund, ShipTicketSale $sale, array $data): void
    {
        $refund->update([
            'refunded_number_of_tickets' => $data['refunded_number_of_tickets'],
            'refunded_amount' => $data['refunded_amount'],
        ]);

        $sale->status = ($sale->number_of_ticket == $data['refunded_number_of_tickets'])
            ? 'refunded'
            : 'partial-refunded';
        $sale->save();
    }
}
