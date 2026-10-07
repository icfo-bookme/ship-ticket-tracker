<?php

use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

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

    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('dashboard.view', 'web'),
        Permission::findOrCreate('sales.view', 'web'),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHas('pendingTickets', 1)
        ->assertSee('Recent Ticket Transactions')
        ->assertSee('Latest activities in the system')
        ->assertDontSee('>Filter<', false);
});
