<?php

use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

it('renders the dashboard with metrics from the service', function () {
    $this->mock(DashboardMetricsService::class, function ($mock): void {
        $mock->shouldReceive('metrics')
            ->once()
            ->andReturn([
                'pendingTickets' => 1,
                'paymentVerified' => 0,
                'ticketIssued' => 0,
                'ticketPrinted' => 0,
                'parcelsCreated' => 0,
                'shipped' => 0,
                'totalPayable' => 100,
                'totalReceived' => 50,
                'totalDue' => 50,
                'recentTickets' => new Collection,
                'shipTicketCounts' => new Collection,
                'companyTicketCounts' => new Collection,
                'upcomingJourneys' => new Collection,
                'topSellerDetails' => new Collection,
                'monthlySales' => new Collection,
                'salesByStatus' => new Collection,
                'dailySales' => new Collection,
            ]);
    });

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHas('pendingTickets', 1);
});
