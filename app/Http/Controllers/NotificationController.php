<?php

namespace App\Http\Controllers;

use App\Models\Bftn;
use App\Models\ShipTicketSale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Bftn::where('bftn_date_time', '<=', Carbon::today()->endOfDay())
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $notificationCounts = Bftn::where('bftn_date_time', '<=', Carbon::today()->endOfDay())->where('notifications_status', 1)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $data = $notifications->map(function ($item) {

            $sale = ShipTicketSale::find($item->sales_id);

            $customerName = $sale->customer_name ?? 'Customer';
            $whatsapp = $sale->whatsapp ?? 'N/A';

            $dateTime = Carbon::parse($item->bftn_date_time)
                ->format('d M Y, h:i A');

            return [
                'id' => $item->id,
                'notification' =>
                "{$customerName} ({$whatsapp}) – Sales BFTN tentative deposit at {$dateTime}",
                'isActive' => $item->notifications_status == 1,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'redirectUrl' => "/ship-ticket-sales/{$item->sales_id}",
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $notificationCounts->count(),
            'data' => $data,
        ]);
    }

    public function verify($id)
    {
        $notify = Bftn::find($id);
        if (!$notify) {
            abort(404);
        }
        $notify->notifications_status = 0;
        $notify->save();
        return redirect()->back();


        // return redirect($notify->redirectUrl);
    }
}
