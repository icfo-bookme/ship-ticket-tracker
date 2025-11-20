<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function partial_due_payment(Request $request, $id)
    {

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Bkash,Nagad,Bank Transfer',
            'remark' => 'nullable|string|max:500'
        ]);

        $sale = ShipTicketSale::findOrFail($id);

        $payment = Payment::create([
            'sales_id' => $id,
            'payment_method' => $validated['payment_method'],
            'received_amount' => $validated['paid_amount'],
            'remark' => $validated['remark'] ?? null,
        ]);

        $sale->received_amount += $validated['paid_amount'];
        $sale->due_amount -= $validated['paid_amount'];

        if ($sale->due_amount < 0) {
            $sale->due_amount = 0;
        }

        $sale->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment' => $payment,
            'sale' => $sale
        ]);
    }

     public function all_due_payment(Request $request, $id)
    {

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Bkash,Nagad,Bank Transfer',
            'remark' => 'nullable|string|max:500'
        ]);

        $sale = ShipTicketSale::findOrFail($id);

        $payment = Payment::create([
            'sales_id' => $id,
            'payment_method' => $validated['payment_method'],
            'received_amount' => $validated['paid_amount'],
            'remark' => $validated['remark'] ?? null,
        ]);

        $sale->received_amount += $validated['paid_amount'];
        $sale->due_amount -= $validated['paid_amount'];

        if ($sale->due_amount < 0) {
            $sale->due_amount = 0;
        }

        $sale->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment' => $payment,
            'sale' => $sale
        ]);
    }
}
