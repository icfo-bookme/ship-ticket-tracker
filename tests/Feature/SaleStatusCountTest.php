<?php

use App\Models\PrintedTicket;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Sales\SalesDataTableService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));
    $this->ship = Ship::create(['name' => 'Counter Ship', 'status' => 1]);
    $this->package = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Deck', 'price' => 500]);
});

function counterSale(ShipPackage $package, string $status): ShipTicketSale
{
    $sale = ShipTicketSale::create([
        'customer_name' => 'Counter Customer', 'customer_mobile' => '01712345678',
        'ship_id' => $package->ship_id, 'journey_date' => '2026-10-29',
        'issued_date' => '2026-10-28', 'sales_source' => 'Direct', 'status' => $status,
    ]);
    $sale->categories()->create(['package_id' => $package->id, 'quantity' => 1, 'unit_price' => 500, 'type' => 'departure']);

    return $sale;
}

it('requires sale access and sends only permitted status counts', function () {
    $url = route('sales.status-counts');
    $this->getJson($url)->assertUnauthorized();
    $user = User::factory()->create();
    $this->actingAs($user)->getJson($url)->assertForbidden();
    $user->givePermissionTo(Permission::findOrCreate('sales.view', 'web'));
    $this->getJson($url)->assertOk()->assertExactJson([]);
    $user->givePermissionTo(Permission::findOrCreate('sales.status.pending', 'web'));
    counterSale($this->package, 'pending');
    counterSale($this->package, 'payment-verified');
    $this->getJson($url)->assertOk()->assertExactJson(['pending' => 1]);
});

it('returns the five counters including zeroes without shipped or collected', function () {
    counterSale($this->package, 'pending');
    counterSale($this->package, 'pending');
    counterSale($this->package, 'shipped');
    counterSale($this->package, 'collect_from_office');
    $this->actingAs($this->admin)->getJson(route('sales.status-counts'))->assertOk()->assertExactJson([
        'pending' => 2, 'payment-verified' => 0, 'ticket-issued' => 0,
        'ticket-printed' => 0, 'shipment_id_entered' => 0,
    ])->assertHeader('Cache-Control', 'no-store, private');
});

it('counts each configured status and refreshes immediately after a sale changes status', function () {
    foreach (array_keys(config('sales.navbar_statuses')) as $status) {
        counterSale($this->package, $status);
    }
    $response = $this->actingAs($this->admin)->getJson(route('sales.status-counts'))->assertOk();
    expect(array_values($response->json()))->toBe([1, 1, 1, 1, 1]);

    ShipTicketSale::query()->where('status', 'pending')->firstOrFail()->update(['status' => 'payment-verified']);
    $this->getJson(route('sales.status-counts'))->assertOk()
        ->assertJsonPath('pending', 0)->assertJsonPath('payment-verified', 2);
});

it('matches table totals for grouped refunded deleted and uncategorized sales', function () {
    $issued = counterSale($this->package, 'ticket-issued');
    $printed = counterSale($this->package, 'ticket-printed');
    foreach ([$issued, $printed, $issued] as $sale) {
        PrintedTicket::create(['sales_id' => $sale->id, 'group_by_id' => $issued->id, 'filename' => uniqid().'.pdf']);
    }
    $deleted = counterSale($this->package, 'pending');
    $deleted->delete();
    $refunded = counterSale($this->package, 'pending');
    Refund::create(['sales_id' => $refunded->id, 'refunded_number_of_tickets' => 1, 'refunded_amount' => 500, 'status' => 'requested']);
    $cancelled = counterSale($this->package, 'pending');
    Refund::create(['sales_id' => $cancelled->id, 'refunded_number_of_tickets' => 1, 'refunded_amount' => 500, 'status' => 'cancelled']);
    $empty = counterSale($this->package, 'pending');
    $empty->categories()->delete();
    $response = $this->actingAs($this->admin)->getJson(route('sales.status-counts', ['ship_id' => 999, 'search' => ['value' => 'ignored']]))->assertOk();
    foreach ($response->json() as $status => $count) {
        $table = app(SalesDataTableService::class)->response(new Request(['length' => 1]), $status)->getData(true);
        expect($count)->toBe($table['recordsTotal']);
    }
    expect($response->json('ticket-issued'))->toBe(2)
        ->and($response->json('ticket-printed'))->toBe(2)
        ->and($response->json('pending'))->toBe(1);
});

it('renders only allowed navbar icons and identifies the active status', function () {
    $user = User::factory()->create();
    foreach (['sales.view', 'sales.status.pending'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user)->get('/profile')->assertOk()
        ->assertSee('data-status-counter="pending"', false)
        ->assertDontSee('data-status-counter="payment-verified"', false)
        ->assertDontSee('data-status-counter="shipped"', false)
        ->assertDontSee('data-status-counter="collect_from_office"', false);
    $this->actingAs($this->admin)->get(route('sales.index', 'payment-verified'))->assertOk()
        ->assertSee('aria-current="page"', false)->assertSee('data-count-tooltip', false);
});

it('gets all five counts in one query even when the sales table is empty', function () {
    $connection = (new ShipTicketSale)->getConnection();
    $connection->enableQueryLog();
    try {
        $counts = app(\App\Services\Sales\SaleStatusCountService::class)->counts($this->admin);
        $queries = collect($connection->getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'ship_ticket_sales'));
        expect(array_values($counts))->toBe([0, 0, 0, 0, 0])->and($queries)->toHaveCount(1);
    } finally {
        $connection->disableQueryLog();
    }
});

it('does not render counters without sale access', function () {
    $this->actingAs(User::factory()->create())->get('/profile')->assertOk()
        ->assertDontSee('data-sale-status-counters', false);
});
