<?php

use App\Models\Category;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Refunds\RefundService;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));
    $this->actingAs($this->admin);
    $ship = Ship::create(['name' => 'Repeat Refund Ship']);
    $package = ShipPackage::create(['ship_id' => $ship->id, 'name' => 'Deck', 'price' => 100]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Repeat Customer', 'customer_mobile' => '01712345678',
        'ship_id' => $ship->id, 'total_payable' => 360, 'discount_amount' => 40,
        'issued_date' => now()->toDateString(), 'status' => 'ticket-issued',
    ]);
    $this->departure = $this->sale->categories()->create(['package_id' => $package->id, 'type' => 'departure', 'quantity' => 2, 'unit_price' => 100]);
    $this->return = $this->sale->categories()->create(['package_id' => $package->id, 'type' => 'return', 'quantity' => 2, 'unit_price' => 100]);
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 360, 'is_bftn' => false, 'settlement_status' => 'received']);
});

function repeatRefundPayload(Category $category, int $quantity): array
{
    return [
        'refunded_number_of_tickets' => $quantity, 'refunded_amount' => $quantity * (float) $category->unit_price,
        'customer_charge_percent' => 0, 'partner_share_percent' => 0,
        'ticket_selections' => [['category_id' => $category->id, 'refunded_quantity' => $quantity]],
    ];
}

function completeRepeatRefund(Refund $refund): void
{
    $service = app(RefundService::class);
    $service->approve($refund);
    $service->addPaymentDetails($refund->refresh(), 'Cash refund');
    $service->refundCustomer($refund->refresh(), ['payment_method' => 'Cash']);
}

it('creates a second request for remaining tickets after completion and preserves the first settlement', function () {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 2))->assertOk();
    $first = Refund::query()->sole();
    completeRepeatRefund($first);
    $before = $first->refresh()->getAttributes();
    $this->sale->update(['status' => 'partial-refunded']);

    $this->getJson(route('refunds.refundable'))->assertOk()->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.can_bulk_refund', false)
        ->assertJsonPath('data.0.refundable_categories.0.remaining_quantity', 0)
        ->assertJsonPath('data.0.refundable_categories.1.remaining_quantity', 2);
    $this->getJson(route('refunds.requests.data', ['status' => 'completed']))->assertOk()
        ->assertJsonPath('data.0.can_refund_remaining', true);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->return, 2))->assertOk();
    $second = Refund::query()->latest('id')->first();
    expect($second->id)->not->toBe($first->id)
        ->and($second->status)->toBe('requested')
        ->and($second->sales_id)->toBe($first->sales_id)
        ->and($first->fresh()->getAttributes())->toBe($before);
    $this->getJson(route('refunds.refundable'))->assertOk()->assertJsonPath('recordsFiltered', 0);
    $this->getJson(route('refunds.requests.data', ['status' => 'completed']))->assertOk()
        ->assertJsonPath('data.0.can_refund_remaining', false);
    completeRepeatRefund($second);
    expect(Refund::query()->count())->toBe(2)
        ->and((float) Refund::query()->sum('refund_discount_amount'))->toBe(40.0)
        ->and((float) $first->customerPayments()->sum('amount'))->toBe(180.0)
        ->and((float) $second->customerPayments()->sum('amount'))->toBe(180.0);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->return, 1))->assertUnprocessable();
});

it('requires editing the current request rather than creating another before completion', function (string $status) {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 1))->assertOk();
    $refund = Refund::query()->sole();
    $refund->update(['status' => $status]);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->return, 1))
        ->assertUnprocessable()->assertJsonValidationErrors('ticket_selections');
    $this->putJson(route('refunds.update', $refund), repeatRefundPayload($this->departure, 2))->assertOk();
    expect(Refund::query()->count())->toBe(1)->and($refund->fresh()->refunded_number_of_tickets)->toBe(2);
    $this->getJson(route('refunds.refundable'))->assertOk()->assertJsonPath('recordsFiltered', 0);
})->with(['requested', 'partner_approved', 'payment_details_added']);

it('frees cancelled ticket quantities and ignores extra payment refunds', function () {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 1))->assertOk();
    app(RefundService::class)->cancel(Refund::query()->sole());
    $this->sale->refunds()->create(['refund_type' => 'extra_payment', 'status' => 'requested', 'refunded_number_of_tickets' => 0, 'refunded_amount' => 10]);
    $this->getJson(route('refunds.refundable'))->assertOk()->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.can_bulk_refund', true)
        ->assertJsonPath('data.0.refundable_categories.0.remaining_quantity', 2);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 2))->assertOk();
});

it('blocks an already consumed category even while another journey has remaining tickets', function () {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 2))->assertOk();
    completeRepeatRefund(Refund::query()->sole());
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 1))
        ->assertUnprocessable()->assertJsonValidationErrors('ticket_selections');
    expect(Refund::query()->count())->toBe(1);
});

it('blocks duplicate category submissions through HTTP and direct service calls', function () {
    $payload = repeatRefundPayload($this->departure, 1);
    $payload['ticket_selections'][] = $payload['ticket_selections'][0];
    $this->postJson(route('refunds.partial', $this->sale), $payload)->assertUnprocessable();
    expect(fn () => app(RefundService::class)->partialRefund($this->sale, $payload))->toThrow(ValidationException::class);
    expect(Refund::query()->count())->toBe(0);
});

it('keeps bulk refunds full and rejects a previously refunded sale atomically', function () {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 1))->assertOk();
    completeRepeatRefund(Refund::query()->sole());
    $this->postJson(route('refunds.full'), ['ids' => [$this->sale->id]])->assertUnprocessable();
    expect(Refund::query()->count())->toBe(1);
});

it('still requests every purchased ticket in a fresh bulk full refund', function () {
    $this->postJson(route('refunds.full'), ['ids' => [$this->sale->id]])->assertOk();
    $refund = Refund::query()->sole();
    expect($refund->refund_type)->toBe('bulk')->and($refund->refunded_number_of_tickets)->toBe(4)
        ->and((float) $refund->gross_refund_amount)->toBe(400.0)
        ->and((float) $refund->refund_discount_amount)->toBe(40.0)
        ->and($refund->tickets()->sum('refunded_quantity'))->toBe(4);
    $this->postJson(route('refunds.full'), ['ids' => [$this->sale->id]])->assertUnprocessable();
    expect(Refund::query()->count())->toBe(1);
});

it('does not offer repeat refunds for legacy requests with no category breakdown', function () {
    $this->sale->refunds()->create(['status' => 'completed', 'refund_type' => 'partial', 'refunded_number_of_tickets' => 1, 'refunded_amount' => 100, 'requested_at' => now()]);
    $this->getJson(route('refunds.refundable'))->assertOk()->assertJsonPath('recordsFiltered', 0);
    $this->getJson(route('refunds.requests.data', ['status' => 'completed']))->assertOk()->assertJsonPath('data.0.can_refund_remaining', false);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->return, 1))->assertUnprocessable();
});

it('keeps cumulative discount within the sale discount across rounded repeated refunds', function () {
    $this->departure->update(['quantity' => 3, 'unit_price' => 0.01]);
    $this->return->delete();
    $this->sale->update(['total_payable' => 0.01, 'discount_amount' => 0.02]);
    for ($index = 0; $index < 3; $index++) {
        $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure->refresh(), 1))->assertOk();
        completeRepeatRefund(Refund::query()->latest('id')->first());
    }
    expect((float) Refund::query()->sum('refund_discount_amount'))->toBe(0.02)
        ->and((float) Refund::query()->sum('customer_refund_amount'))->toBe(0.01);
});

it('applies remaining due only once across completed ticket refunds', function () {
    $this->sale->payments()->delete();
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 300, 'is_bftn' => false, 'settlement_status' => 'received']);
    foreach ([$this->departure, $this->return] as $category) {
        $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($category, 2))->assertOk();
        completeRepeatRefund(Refund::query()->latest('id')->first());
    }
    expect((float) Refund::query()->sum('due_adjusted_amount'))->toBe(60.0)
        ->and((float) $this->sale->fresh()->due_amount)->toBe(0.0);
    $this->getJson(route('refunds.requests.data', ['status' => 'completed']))->assertOk()
        ->assertJsonPath('data.0.due_adjusted_amount', '60.00')
        ->assertJsonPath('data.1.due_adjusted_amount', '0.00');
    $this->getJson(route('refund-reports.data'))->assertOk()
        ->assertJsonPath('totals.refunded_count', 2)
        ->assertJsonPath('totals.due_adjusted_amount', '60.00')
        ->assertJsonPath('totals.discount_amount', '40.00')
        ->assertJsonPath('totals.customer_refund_paid', '300.00');
});

it('rejects another sales category and hides historical refunds with mismatched category ownership', function () {
    $otherSale = $this->sale->replicate();
    $otherSale->save();
    $foreignCategory = $otherSale->categories()->create([
        'package_id' => $this->departure->package_id, 'type' => 'departure', 'quantity' => 2, 'unit_price' => 100,
    ]);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($foreignCategory, 1))
        ->assertUnprocessable()->assertJsonValidationErrors('ticket_selections');
    $refund = $this->sale->refunds()->create([
        'status' => 'completed', 'refund_type' => 'partial', 'refunded_number_of_tickets' => 1,
        'refunded_amount' => 100, 'requested_at' => now(),
    ]);
    $refund->tickets()->create(['category_id' => $foreignCategory->id, 'refunded_quantity' => 1]);
    $response = $this->getJson(route('refunds.refundable'))->assertOk();
    expect(collect($response->json('data'))->pluck('id')->all())->not->toContain($this->sale->id);
    $this->getJson(route('refunds.requests.data', ['status' => 'completed']))->assertOk()
        ->assertJsonPath('data.0.can_refund_remaining', false);
});

it('allows remaining tickets despite a legacy fully refunded sale status', function () {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 1))->assertOk();
    completeRepeatRefund(Refund::query()->sole());
    $this->sale->update(['status' => 'refunded']);
    $this->getJson(route('refunds.refundable', ['length' => 1]))->assertOk()
        ->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.id', $this->sale->id);
});

it('enforces create permission when requesting remaining tickets', function () {
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->departure, 1))->assertOk();
    completeRepeatRefund(Refund::query()->sole());
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('refunds.view', 'web'));
    $this->actingAs($viewer)->get('/refunded')->assertOk()->assertSee('data-can-refund="0"', false);
    $this->postJson(route('refunds.partial', $this->sale), repeatRefundPayload($this->return, 1))->assertForbidden();
    expect(Refund::query()->count())->toBe(1);
});
