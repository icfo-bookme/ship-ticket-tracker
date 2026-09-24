<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\SaveDuePaymentRequest;
use App\Models\Payment;
use App\Services\Finance\PaymentProofStorage;
use App\Services\Finance\PaymentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentProofStorage $proofs,
    ) {}

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

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($this->proofs->exists($payment->payment_proof), 404);

        return $this->proofs->response($payment->payment_proof);
    }
}
