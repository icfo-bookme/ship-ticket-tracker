<?php

use App\Models\Category;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Reports\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('soft deletes a sale, retains its financial history, excludes it from reports, and can restore it', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('sales.delete', 'web'));

    $ship = Ship::query()->create(['name' => 'Soft delete test ship']);
    $package = ShipPackage::query()->create([
        'ship_id' => $ship->id,
        'name' => 'Soft delete test package',
        'price' => 100,
    ]);
    $sale = ShipTicketSale::query()->create([
        'customer_name' => 'Soft delete test customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'issued_date' => now()->toDateString(),
        'status' => 'ticket-issued',
        'total_payable' => 100,
    ]);
    Category::query()->create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'type' => 'departure',
        'unit_price' => 100,
    ]);
    $payment = Payment::query()->create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 100,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('ship-ticket-sales.destroy', $sale))
        ->assertOk();

    $this->get(route('ship-ticket-sales.deleted.index'))
        ->assertOk()
        ->assertSee('Deleted Sales');

    $this->getJson(route('ship-ticket-sales.deleted.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('data.0.id', $sale->id);

    expect(ShipTicketSale::query()->find($sale->id))->toBeNull()
        ->and(ShipTicketSale::withTrashed()->find($sale->id))->not->toBeNull()
        ->and(Payment::query()->find($payment->id))->not->toBeNull()
        ->and($payment->fresh()->sale)->not->toBeNull();

    $report = app(SalesReportService::class)->dataTable(Request::create('/admin/sales-reports'));

    expect($report['recordsTotal'])->toBe(0)
        ->and($report['totals']['total_payable'])->toBe('0.00')
        ->and(app(DashboardMetricsService::class)->metrics()['totalReceived'])->toBe(0);

    $this->actingAs($user)
        ->putJson(route('ship-ticket-sales.restore', $sale->id))
        ->assertOk()
        ->assertJsonPath('data.id', $sale->id);

    expect(ShipTicketSale::query()->find($sale->id))->not->toBeNull();

    $this->getJson(route('ship-ticket-sales.deleted.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 0);
});
