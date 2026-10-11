<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Notifications\BftnNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function __construct(private readonly BftnNotificationService $bftnNotifications) {}

    public function index(): JsonResponse
    {
        return response()->json($this->bftnNotifications->payload());
    }

    public function verify(Payment $payment): RedirectResponse
    {
        $this->bftnNotifications->markAsRead($payment);

        return redirect()->back();
    }
}
