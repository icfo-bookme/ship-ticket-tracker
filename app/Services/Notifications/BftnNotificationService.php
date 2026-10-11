<?php

namespace App\Services\Notifications;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BftnNotificationService
{
    /**
     * @return array{success: bool, count: int, data: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    public function payload(): array
    {
        $notifications = $this->dueNotifications();

        return [
            'success' => true,
            'count' => $notifications
                ->whereNull('bftn_notification_read_at')
                ->count(),
            'data' => $notifications->map(fn (Payment $payment): array => $this->format($payment)),
        ];
    }

    public function markAsRead(Payment $payment): void
    {
        $payment->update(['bftn_notification_read_at' => now()]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Payment>
     */
    private function dueNotifications(): Collection
    {
        return Payment::with('sale')
            ->where('is_bftn', true)
            ->whereNotNull('bftn_issue_datetime')
            ->where('bftn_issue_datetime', '<=', Carbon::today()->endOfDay())
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Payment $payment): array
    {
        $sale = $payment->sale;
        $customerName = $sale?->customer_name ?? 'Customer';
        $whatsapp = $sale?->whatsapp ?? 'N/A';
        $dateTime = Carbon::parse($payment->bftn_issue_datetime)->format('d M Y, h:i A');
        $paymentAmount = number_format((float) $payment->received_amount, 2);

        return [
            'id' => $payment->id,
            'notification' => "{$customerName} ({$whatsapp}) - BFTN payment of ৳{$paymentAmount}, tentative deposit at {$dateTime}",
            'isActive' => $payment->bftn_notification_read_at === null,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
            'redirectUrl' => $sale ? "/ship-ticket-sales/{$sale->id}" : null,
        ];
    }
}
