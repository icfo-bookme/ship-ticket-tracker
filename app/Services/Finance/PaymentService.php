<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\ShipTicketSale;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * @return array{payment: Payment, sale: ShipTicketSale}
     */
    public function payDue(int|string $saleId, array $data): array
    {
        return DB::transaction(function () use ($saleId, $data): array {
            $sale = ShipTicketSale::findOrFail($saleId);

            $payment = Payment::create([
                'sales_id' => $saleId,
                'payment_method' => $data['payment_method'],
                'received_amount' => $data['paid_amount'],
                'remark' => $data['remark'] ?? null,
            ]);

            $sale->received_amount += $data['paid_amount'];
            $sale->due_amount -= $data['paid_amount'];

            if ($sale->due_amount < 0) {
                $sale->due_amount = 0;
            }

            $sale->save();

            return ['payment' => $payment, 'sale' => $sale];
        });
    }
}
