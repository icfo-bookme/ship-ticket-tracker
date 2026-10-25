<?php

namespace App\Services\Dashboard;

use App\Enums\SaleStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        $statusCounts = $this->statusCounts();
        $revenueOverview = $this->revenueOverview();

        return [
            'pendingTickets' => $statusCounts[SaleStatus::Pending->value] ?? 0,
            'paymentVerified' => $statusCounts[SaleStatus::PaymentVerified->value] ?? 0,
            'ticketIssued' => $statusCounts[SaleStatus::TicketIssued->value] ?? 0,
            'ticketPrinted' => $statusCounts[SaleStatus::TicketPrinted->value] ?? 0,
            'parcelsCreated' => $statusCounts[SaleStatus::ShipmentIdEntered->value] ?? 0,
            'shipped' => $statusCounts[SaleStatus::Shipped->value] ?? 0,
            'collectFromOffice' => $statusCounts[SaleStatus::CollectFromOffice->value] ?? 0,
            'totalPayable' => $revenueOverview->total_payable ?? 0,
            'totalReceived' => $revenueOverview->total_received ?? 0,
            'totalDue' => $revenueOverview->total_due ?? 0,
            'recentTickets' => $this->recentTickets(),
            'shipTicketCounts' => $this->shipTicketCounts(),
            'companyTicketCounts' => $this->companyTicketCounts(),
            'upcomingJourneys' => $this->upcomingJourneys(),
            'topSellerDetails' => $this->topSellerDetails(),
            'monthlySales' => $this->monthlySales(),
            'salesByStatus' => $this->salesByStatus(),
            'dailySales' => $this->dailySales(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        return ShipTicketSale::query()
            ->pluck('status')
            ->countBy()
            ->all();
    }

    private function revenueOverview(): object
    {
        return (object) [
            'total_payable' => ShipTicketSale::query()
                ->selectRaw('COALESCE(SUM((SELECT COALESCE(SUM(categories.quantity * categories.unit_price), 0) FROM categories WHERE categories.ticket_id = ship_ticket_sales.id)), 0) AS total')
                ->value('total'),
            'total_received' => Payment::query()
                ->whereHas('sale', fn (Builder $sales) => $sales->whereNull('deleted_at'))
                ->where(fn ($query) => $query->where('is_bftn', false)->orWhere('settlement_status', 'received'))
                ->sum('received_amount'),
            'total_due' => $this->totalDue(),
        ];
    }

    private function totalDue(): float
    {
        $paymentTotals = Payment::query()
            ->selectRaw(<<<'SQL'
                sales_id,
                SUM(CASE WHEN is_bftn = 0 OR settlement_status = "received" THEN received_amount ELSE 0 END) AS settled_amount,
                SUM(CASE WHEN is_bftn = 1 AND settlement_status = "pending" THEN received_amount ELSE 0 END) AS pending_bftn_amount
                SQL)
            ->groupBy('sales_id');

        $refundTotals = Refund::query()
            ->where(fn ($refunds) => $refunds
                ->where('status', 'completed')
                ->orWhereNotNull('customer_refunded_at'))
            ->selectRaw('sales_id, SUM(due_adjusted_amount) AS due_adjusted_amount')
            ->groupBy('sales_id');

        return (float) ShipTicketSale::query()
            ->leftJoinSub($paymentTotals, 'payment_totals', 'payment_totals.sales_id', '=', 'ship_ticket_sales.id')
            ->leftJoinSub($refundTotals, 'refund_totals', 'refund_totals.sales_id', '=', 'ship_ticket_sales.id')
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(CASE
                    WHEN COALESCE(ship_ticket_sales.total_payable, 0)
                        - COALESCE(payment_totals.settled_amount, 0)
                        - COALESCE(payment_totals.pending_bftn_amount, 0)
                        - COALESCE(refund_totals.due_adjusted_amount, 0) > 0
                    THEN COALESCE(ship_ticket_sales.total_payable, 0)
                        - COALESCE(payment_totals.settled_amount, 0)
                        - COALESCE(payment_totals.pending_bftn_amount, 0)
                        - COALESCE(refund_totals.due_adjusted_amount, 0)
                    ELSE 0
                END), 0) AS total
                SQL)
            ->value('total');
    }

    private function recentTickets()
    {
        return ShipTicketSale::with(['ships', 'companies', 'categories', 'payments', 'refunds'])
            ->latest()
            ->limit(10)
            ->get();
    }

    private function shipTicketCounts()
    {
        return Ship::withCount('shipTicketSales')->get();
    }

    private function companyTicketCounts()
    {
        return Company::withCount('shipTicketSales')->get();
    }

    private function upcomingJourneys()
    {
        $today = Carbon::today();

        return ShipTicketSale::with(['ships', 'categories', 'payments', 'refunds'])
            ->whereBetween('journey_date', [$today, $today->copy()->addDays(7)])
            ->get();
    }

    private function topSellerDetails()
    {
        return ShipTicketSale::query()
            ->with('seller:id,name,email')
            ->whereNotNull('sold_by')
            ->with(['payments', 'categories'])
            ->get(['id', 'sold_by'])
            ->groupBy('sold_by')
            ->filter(fn ($sales): bool => $sales->first()->seller !== null)
            ->map(function ($sales): object {
                $sellerSale = $sales->first();
                $totalRevenue = (float) $sales->sum('ticket_value');
                $totalCollected = (float) $sales->sum(fn (ShipTicketSale $sale): float => (float) $sale->payments
                    ->reject(fn ($payment): bool => $payment->is_bftn && $payment->settlement_status !== 'received')
                    ->sum('received_amount'));

                return (object) [
                    'id' => $sellerSale->seller->id,
                    'name' => $sellerSale->seller->name,
                    'email' => $sellerSale->seller->email,
                    'total_tickets' => $sales->count(),
                    'total_revenue' => $totalRevenue,
                    'total_collected' => $totalCollected,
                    'efficiency' => $totalRevenue > 0
                        ? round(($totalCollected / $totalRevenue) * 100, 2)
                    : 0,
                ];
            })
            ->sortByDesc('total_tickets')
            ->take(3)
            ->values();
    }

    private function monthlySales()
    {
        return ShipTicketSale::query()
            ->where('created_at', '>=', now()->subMonths(6))
            ->with('categories')
            ->get(['id', 'created_at'])
            ->groupBy(fn (ShipTicketSale $sale): string => $sale->created_at->format('Y-m'))
            ->sortKeys()
            ->map(fn ($sales, string $month): object => (object) [
                'month' => $month,
                'ticket_count' => $sales->count(),
                'total_revenue' => $sales->sum('ticket_value'),
            ])
            ->values();
    }

    private function salesByStatus()
    {
        return ShipTicketSale::query()
            ->with('categories')
            ->get(['id', 'status'])
            ->groupBy('status')
            ->map(fn ($sales, string $status): object => (object) [
                'status' => $status,
                'count' => $sales->count(),
                'total_amount' => $sales->sum('ticket_value'),
            ])
            ->values();
    }

    private function dailySales()
    {
        return ShipTicketSale::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->with('categories')
            ->get(['id', 'created_at'])
            ->groupBy(fn (ShipTicketSale $sale): string => $sale->created_at->toDateString())
            ->sortKeys()
            ->map(fn ($sales, string $date): object => (object) [
                'date' => $date,
                'tickets_sold' => $sales->count(),
                'daily_revenue' => $sales->sum('ticket_value'),
            ])
            ->values();
    }
}
