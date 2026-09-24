<?php

namespace App\Services\Notifications;

use App\Models\Bftn;
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
                ->where('notifications_status', 1)
                ->count(),
            'data' => $notifications->map(fn (Bftn $notification): array => $this->format($notification)),
        ];
    }

    public function markAsRead(Bftn $notification): void
    {
        $notification->notifications_status = 0;
        $notification->save();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Bftn>
     */
    private function dueNotifications(): Collection
    {
        return Bftn::with('sale')
            ->where('bftn_date_time', '<=', Carbon::today()->endOfDay())
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Bftn $notification): array
    {
        $customerName = $notification->sale?->customer_name ?? 'Customer';
        $whatsapp = $notification->sale?->whatsapp ?? 'N/A';
        $dateTime = Carbon::parse($notification->bftn_date_time)->format('d M Y, h:i A');

        return [
            'id' => $notification->id,
            'notification' => "{$customerName} ({$whatsapp}) - Sales BFTN tentative deposit at {$dateTime}",
            'isActive' => $notification->notifications_status == 1,
            'created_at' => $notification->created_at,
            'updated_at' => $notification->updated_at,
            'redirectUrl' => "/ship-ticket-sales/{$notification->sales_id}",
        ];
    }
}
