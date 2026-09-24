<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\ShipTicketSale;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly PaymentProofStorage $paymentProofs) {}

    /**
     * @param  array{paid_amount: int|float|string, other_fee?: int|float|string|null, discount_amount?: int|float|string|null, transaction_id?: string|null, payment_proof?: \Illuminate\Http\UploadedFile|null, payment_method: string, remark?: string|null}  $data
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
                'transaction_id' => $data['transaction_id'] ?? null,
                'payment_proof' => $this->paymentProofs->store($data['payment_proof'] ?? null),
                'paid_date' => now()->toDateString(),
                'payment_datetime' => now(),
                'remark' => $data['remark'] ?? null,
            ]);

            $otherFee = (float) ($data['other_fee'] ?? 0);

            if ($otherFee > 0) {
                $sale->other_fee += $otherFee;
                $sale->total_payable += $otherFee;
                $sale->due_amount += $otherFee;
            }

            $discount = (float) ($data['discount_amount'] ?? 0);

            if ($discount > 0) {
                $sale->discount_amount += $discount;
                $sale->total_payable -= $discount;
                $sale->due_amount -= $discount;
            }

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
