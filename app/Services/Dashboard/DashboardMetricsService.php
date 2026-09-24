<?php

namespace App\Services\Dashboard;

use App\Enums\SaleStatus;
use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use Carbon\Carbon;

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
            'total_payable' => ShipTicketSale::sum('ticket_fee'),
            'total_received' => ShipTicketSale::sum('received_amount'),
            'total_due' => ShipTicketSale::sum('due_amount'),
        ];
    }

    private function recentTickets()
    {
        return ShipTicketSale::with(['ships', 'companies'])
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

        return ShipTicketSale::with('ships')
            ->whereBetween('journey_date', [$today, $today->copy()->addDays(7)])
            ->get();
    }

    private function topSellerDetails()
    {
        return ShipTicketSale::query()
            ->with('seller:id,name,email')
            ->whereNotNull('sold_by')
            ->get(['id', 'sold_by', 'ticket_fee', 'received_amount'])
            ->groupBy('sold_by')
            ->filter(fn ($sales): bool => $sales->first()->seller !== null)
            ->map(function ($sales): object {
                $sellerSale = $sales->first();
                $totalRevenue = (float) $sales->sum('ticket_fee');
                $totalCollected = (float) $sales->sum('received_amount');

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
            ->get(['id', 'ticket_fee', 'created_at'])
            ->groupBy(fn (ShipTicketSale $sale): string => $sale->created_at->format('Y-m'))
            ->sortKeys()
            ->map(fn ($sales, string $month): object => (object) [
                'month' => $month,
                'ticket_count' => $sales->count(),
                'total_revenue' => $sales->sum('ticket_fee'),
            ])
            ->values();
    }

    private function salesByStatus()
    {
        return ShipTicketSale::query()
            ->get(['id', 'status', 'ticket_fee'])
            ->groupBy('status')
            ->map(fn ($sales, string $status): object => (object) [
                'status' => $status,
                'count' => $sales->count(),
                'total_amount' => $sales->sum('ticket_fee'),
            ])
            ->values();
    }

    private function dailySales()
    {
        return ShipTicketSale::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['id', 'ticket_fee', 'created_at'])
            ->groupBy(fn (ShipTicketSale $sale): string => $sale->created_at->toDateString())
            ->sortKeys()
            ->map(fn ($sales, string $date): object => (object) [
                'date' => $date,
                'tickets_sold' => $sales->count(),
                'daily_revenue' => $sales->sum('ticket_fee'),
            ])
            ->values();
    }
}
