<?php

use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Models\VerifyTracker;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->viewer = User::factory()->create();
    $this->viewer->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));
    $this->ship = Ship::create(['name' => 'Issued Ship', 'status' => 1]);
    $this->actingAs($this->viewer);
});

function issuedReportSale(Ship $ship, array $attributes = []): ShipTicketSale
{
    $sale = ShipTicketSale::create(array_merge([
        'customer_name' => 'Issued Customer', 'customer_mobile' => '01712345678',
        'sales_source' => 'Direct', 'ship_id' => $ship->id,
        'journey_date' => '2026-12-01', 'issued_date' => '2026-09-01',
        'status' => 'shipped', 'other_fee' => 20, 'discount_amount' => 10,
        'total_payable' => 310,
    ], $attributes));
    $package = ShipPackage::create(['ship_id' => $ship->id, 'name' => 'Deck', 'price' => 150]);
    $sale->categories()->create([
        'package_id' => $package->id, 'quantity' => 2, 'unit_price' => 150, 'type' => 'departure',
    ]);
    $sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 100]);

    return $sale;
}

function issuedReportHistory(ShipTicketSale $sale, User $user, string $at, string $status = 'ticket-issued'): VerifyTracker
{
    $history = $sale->verifyby()->create(['name' => $status, 'verified_by' => $user->id]);
    $history->forceFill(['created_at' => $at])->save();

    return $history;
}

it('renders the report with Bangladesh today and shared components', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-04 20:00:00', 'UTC'));
    $this->get(route('ticket-issued-reports.index'))->assertOk()
        ->assertSee('Ticket Issued Report')->assertSee('value="2026-11-05"', false)
        ->assertSee('ticketIssuedTable-page-loader')->assertSee('ticketIssuedDetailModal')
        ->assertSee('data-searchable-multi-select', false)
        ->assertDontSee('Search Sales')->assertDontSee('ticketIssuedCompany')
        ->assertDontSee('ticketIssuedLoading');
});

it('protects both report endpoints', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('ticket-issued-reports.index'))->assertForbidden();
    $this->getJson(route('ticket-issued-reports.data'))->assertForbidden();
});

it('uses issue history regardless of current status and counts duplicate history once', function (string $status) {
    $sale = issuedReportSale($this->ship, ['status' => $status]);
    $sale->forceFill(['created_at' => '2026-09-01 00:00:00'])->save();
    issuedReportHistory($sale, $this->viewer, '2026-11-05 02:00:00');
    issuedReportHistory($sale, $this->viewer, '2026-11-05 03:00:00');
    issuedReportSale($this->ship, ['status' => 'ticket-issued']);
    $this->getJson(route('ticket-issued-reports.data', [
        'ticket_issued_from' => '2026-11-05', 'ticket_issued_to' => '2026-11-05', 'draw' => 3,
    ]))->assertOk()->assertJsonPath('draw', 3)->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $sale->id)->assertJsonPath('data.0.issued_by', $this->viewer->name)
        ->assertJsonPath('data.0.issued_at', '2026-11-05T02:00:00+00:00')
        ->assertJsonCount(2, 'data.0.verification_timeline')
        ->assertJsonPath('totals.total_sales', 1)->assertJsonPath('totals.total_tickets', 2)
        ->assertJsonPath('totals.total_ticket_value', 300)->assertJsonPath('totals.total_payable', 310)
        ->assertJsonPath('totals.total_received_amount', 100)->assertJsonPath('totals.total_due_amount', 210);
})->with(['pending', 'ticket-issued', 'ticket-printed', 'shipped', 'refunded']);

it('applies Bangladesh midnight boundaries to the same history record', function () {
    $expectedIds = [];
    foreach ([['2026-11-04 17:59:59', false], ['2026-11-04 18:00:00', true],
        ['2026-11-05 17:59:59', true], ['2026-11-05 18:00:00', false]] as [$at, $matches]) {
        $sale = issuedReportSale($this->ship);
        issuedReportHistory($sale, $this->viewer, $at);
        if ($matches) {
            $expectedIds[] = $sale->id;
        }
    }
    $outside = issuedReportSale($this->ship);
    issuedReportHistory($outside, $this->viewer, '2026-11-04 00:00:00');
    issuedReportHistory($outside, $this->viewer, '2026-11-06 00:00:00');
    $response = $this->getJson(route('ticket-issued-reports.data', [
        'ticket_issued_from' => '2026-11-05', 'ticket_issued_to' => '2026-11-05',
    ]))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('totals.total_sales', 2);
    expect(collect($response->json('data'))->pluck('id')->all())->toEqualCanonicalizing($expectedIds);
});

it('supports multiple ships and includes all pages in searched totals', function () {
    $secondShip = Ship::create(['name' => 'Second Ship', 'status' => 1]);
    $thirdShip = Ship::create(['name' => 'Third Ship', 'status' => 1]);
    foreach ([$this->ship, $secondShip, $thirdShip] as $ship) {
        issuedReportHistory(issuedReportSale($ship), $this->viewer, '2026-11-05 02:00:00');
    }
    issuedReportHistory(issuedReportSale($this->ship, ['customer_name' => 'Other']), $this->viewer, '2026-11-05 02:00:00');
    $this->getJson(route('ticket-issued-reports.data', [
        'ship_ids' => [$this->ship->id, $secondShip->id], 'search' => ['value' => 'Issued Customer'],
        'length' => 1, 'start' => 1,
    ]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('recordsTotal', 3)
        ->assertJsonPath('recordsFiltered', 2)->assertJsonPath('totals.total_sales', 2)
        ->assertJsonPath('totals.total_tickets', 4)->assertJsonPath('totals.total_ticket_value', 600)
        ->assertJsonPath('totals.total_discount_amount', 20)->assertJsonPath('totals.total_other_fee', 40)
        ->assertJsonPath('totals.total_payable', 620)->assertJsonPath('totals.total_received_amount', 200)
        ->assertJsonPath('totals.total_due_amount', 420);
});

it('supports open ended ranges and no date filters', function (array $filters) {
    $sale = issuedReportSale($this->ship);
    issuedReportHistory($sale, $this->viewer, '2026-11-05 02:00:00');
    $this->getJson(route('ticket-issued-reports.data', $filters))->assertOk()->assertJsonCount(1, 'data');
})->with([
    'from only' => [['ticket_issued_from' => '2026-11-05']],
    'to only' => [['ticket_issued_to' => '2026-11-05']],
    'all history' => [[]],
]);

it('returns empty results and zero totals for non matching or deleted sales', function () {
    $otherEvent = issuedReportSale($this->ship);
    issuedReportHistory($otherEvent, $this->viewer, '2026-11-05 02:00:00', 'payment-verified');
    $deleted = issuedReportSale($this->ship);
    issuedReportHistory($deleted, $this->viewer, '2026-11-05 02:00:00');
    $deleted->delete();
    $this->getJson(route('ticket-issued-reports.data'))->assertOk()->assertJsonCount(0, 'data')
        ->assertJsonPath('totals.total_sales', 0)->assertJsonPath('totals.total_payable', 0);
});

it('uses settled and pending BFTN consistently with existing financial rules', function () {
    $sale = issuedReportSale($this->ship);
    issuedReportHistory($sale, $this->viewer, '2026-11-05 02:00:00');
    $sale->payments()->create(['payment_method' => 'Bank', 'received_amount' => 50, 'is_bftn' => true, 'settlement_status' => 'pending']);
    $sale->payments()->create(['payment_method' => 'Bank', 'received_amount' => 60, 'is_bftn' => true, 'settlement_status' => 'received']);
    $this->getJson(route('ticket-issued-reports.data'))->assertOk()
        ->assertJsonPath('data.0.received_amount', 160)->assertJsonPath('data.0.due_amount', 100)
        ->assertJsonPath('totals.total_received_amount', 160)->assertJsonPath('totals.total_due_amount', 100);
});

it('rejects invalid report filters', function (array $filters, string $field) {
    $this->getJson(route('ticket-issued-reports.data', $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'bad from' => [['ticket_issued_from' => 'invalid'], 'ticket_issued_from'],
    'bad to' => [['ticket_issued_to' => '2026-02-30'], 'ticket_issued_to'],
    'reversed' => [['ticket_issued_from' => '2026-11-06', 'ticket_issued_to' => '2026-11-05'], 'ticket_issued_to'],
    'unknown ship' => [['ship_ids' => [999999]], 'ship_ids.0'],
    'duplicate ships' => [['ship_ids' => [1, 1]], 'ship_ids.0'],
    'invalid ships' => [['ship_ids' => 'invalid'], 'ship_ids'],
    'long search' => [['search' => ['value' => str_repeat('x', 256)]], 'search.value'],
    'negative offset' => [['start' => -1], 'start'],
    'too many rows' => [['length' => 101], 'length'],
]);

it('installs the history lookup index', function () {
    expect(Schema::hasIndex('verify_tracker', 'verify_tracker_ticket_issue_history_index'))->toBeTrue();
});
