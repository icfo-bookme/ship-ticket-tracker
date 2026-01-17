<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShipTicketSale;
use App\Models\Ship;
use App\Models\Company;
use App\Models\User; // Add this import
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Add this import

class DashboardController extends Controller
{
    public function index()
    {
        // -------------------------
        // Ticket counts by status
        // -------------------------
        $pendingTickets = ShipTicketSale::where('status', 'pending')->count();
        $paymentVerified = ShipTicketSale::where('status', 'payment-verified')->count();
        $ticketIssued = ShipTicketSale::where('status', 'ticket-issued')->count();
        $ticketPrinted = ShipTicketSale::where('status', 'ticket-printed')->count();
        $parcelsCreated = ShipTicketSale::where('status', 'parcel-created')->count();
        $shipped = ShipTicketSale::where('status', 'shipped')->count();

        // -------------------------
        // Revenue overview
        // -------------------------
        $totalPayable = ShipTicketSale::sum('ticket_fee');
        $totalReceived = ShipTicketSale::sum('received_amount');
        $totalDue = ShipTicketSale::sum('due_amount');

        // -------------------------
        // Recent tickets (last 10)
        // -------------------------
        $recentTickets = ShipTicketSale::with(['ships', 'companies'])
            ->latest()
            ->take(10)
            ->get();

        // -------------------------
        // Ship-wise ticket count
        // -------------------------
        $shipTicketCounts = Ship::withCount('shipTicketSales')->get();

        // -------------------------
        // Company-wise ticket count
        // -------------------------
        $companyTicketCounts = Company::withCount('shipTicketSales')->get();

        // -------------------------
        // Upcoming journeys (next 7 days)
        // -------------------------
        $today = Carbon::today();
        $upcomingJourneys = ShipTicketSale::whereBetween('journey_date', [$today, $today->copy()->addDays(7)])
            ->with('ships')
            ->get();

        // -------------------------
        // NEW: Top 3 Ticket Sellers (Users)
        // -------------------------
        $topSellers = DB::table('ship_ticket_sales')
            ->select(
                'sold_by',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(ticket_fee) as total_revenue'),
                DB::raw('SUM(received_amount) as total_collected')
            )
            ->whereNotNull('sold_by')
            ->groupBy('sold_by')
            ->orderByDesc('total_tickets')
            ->take(3)
            ->get();

        // Get user details for top sellers
        $topSellerDetails = [];
        foreach ($topSellers as $seller) {
            $user = User::find($seller->sold_by);
            if ($user) {
                $topSellerDetails[] = (object)[
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_tickets' => $seller->total_tickets,
                    'total_revenue' => $seller->total_revenue,
                    'total_collected' => $seller->total_collected,
                    'efficiency' => $seller->total_revenue > 0 ? 
                        round(($seller->total_collected / $seller->total_revenue) * 100, 2) : 0
                ];
            }
        }

        // -------------------------
        // NEW: Monthly Sales Trend (for line chart)
        // -------------------------
        $monthlySales = DB::table('ship_ticket_sales')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as ticket_count'),
                DB::raw('SUM(ticket_fee) as total_revenue')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // -------------------------
        // NEW: Sales by Status (for donut chart)
        // -------------------------
        $salesByStatus = DB::table('ship_ticket_sales')
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(ticket_fee) as total_amount')
            )
            ->groupBy('status')
            ->get();

        // -------------------------
        // NEW: Daily Sales Performance (last 30 days)
        // -------------------------
        $dailySales = DB::table('ship_ticket_sales')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as tickets_sold'),
                DB::raw('SUM(ticket_fee) as daily_revenue')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // -------------------------
        // Passing all data to dashboard view
        // -------------------------
        return view('dashboard', compact(
            'pendingTickets',
            'paymentVerified',
            'ticketIssued',
            'ticketPrinted',
            'parcelsCreated',
            'shipped',
            'totalPayable',
            'totalReceived',
            'totalDue',
            'recentTickets',
            'shipTicketCounts',
            'companyTicketCounts',
            'upcomingJourneys',
            'topSellerDetails', // New
            'monthlySales',     // New
            'salesByStatus',    // New
            'dailySales'        // New
        ));
    }
}