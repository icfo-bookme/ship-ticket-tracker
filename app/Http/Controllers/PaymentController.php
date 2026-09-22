<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\SaveDuePaymentRequest;
use App\Services\Finance\PaymentService;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function partial_due_payment(SaveDuePaymentRequest $request, $id)
    {
        $result = $this->payments->payDue($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment' => $result['payment'],
            'sale' => $result['sale'],
        ]);
    }

    public function all_due_payment(SaveDuePaymentRequest $request, $id)
    {
        $result = $this->payments->payDue($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment' => $result['payment'],
            'sale' => $result['sale'],
        ]);
    }
}
