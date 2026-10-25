<?php

use App\Models\Category;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('calculates dashboard due from grouped payment and refund totals', function () {
    $ship = Ship::query()->create(['name' => 'Due test ship']);
    $sale = ShipTicketSale::query()->create([
        'customer_name' => 'Due test customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'issued_date' => now()->toDateString(),
        'status' => 'ticket-issued',
        'total_payable' => 100,
    ]);
    $package = ShipPackage::query()->create([
        'ship_id' => $ship->id,
        'name' => 'Due test package',
        'price' => 100,
    ]);
    Category::query()->create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'unit_price' => 100,
        'type' => 'departure',
    ]);
    Payment::query()->create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 40,
    ]);
    Payment::query()->create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'pending',
        'received_amount' => 50,
    ]);
    Refund::query()->create([
        'sales_id' => $sale->id,
        'status' => 'completed',
        'refunded_number_of_tickets' => 1,
        'refunded_amount' => 5,
        'due_adjusted_amount' => 5,
    ]);

    expect(app(DashboardMetricsService::class)->metrics()['totalDue'])->toBe(5.0);
});

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
                'collectFromOffice' => 2,
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
        ->assertViewHas('collectFromOffice', 2)
        ->assertSee('Collect From Office')
        ->assertSee('Recent Ticket Transactions')
        ->assertSee('Latest activities in the system')
        ->assertDontSee('>Filter<', false);
});

it('counts office collection sales without including deleted sales', function () {
    $ship = Ship::query()->create(['name' => 'Office collection ship']);
    $sale = ShipTicketSale::query()->create([
        'customer_name' => 'Office customer',
        'customer_mobile' => '01712345678',
        'ship_id' => $ship->id,
        'issued_date' => now()->toDateString(),
        'status' => 'collect_from_office',
    ]);

    expect(app(DashboardMetricsService::class)->metrics()['collectFromOffice'])->toBe(1);

    $sale->delete();

    expect(app(DashboardMetricsService::class)->metrics()['collectFromOffice'])->toBe(0);
});
