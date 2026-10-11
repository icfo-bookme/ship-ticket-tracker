<?php

use App\Models\Category;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->ship = Ship::create(['name' => 'MV Due Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $this->ship->id,
        'name' => 'Due Ticket',
        'price' => 500,
        'round_trip_price' => 800,
    ]);

    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Due Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'journey_date' => '2026-10-01',
        'ticket_value' => 500,
        'other_fee' => 0,
        'total_payable' => 500,
        'due_amount' => 300,
        'issued_date' => '2026-09-23',
        'ticket_count' => 1,
        'status' => 'pending',
    ]);

    Category::create([
        'ticket_id' => $this->sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'type' => 'departure',
        'unit_price' => 500,
    ]);

    Payment::create([
        'sales_id' => $this->sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 200,
    ]);
});

function dueSaleReceivedAmount(ShipTicketSale $sale): float
{
    return (float) $sale->payments()
        ->where(fn ($query) => $query->where('is_bftn', false)->orWhere('settlement_status', 'received'))
        ->sum('received_amount');
}

it('adds the other fee to the sale other fee, total payable and due amount', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'other_fee' => 20,
            'payment_method' => 'Bkash',
            'transaction_id' => 'TRX-11223344',
            'remark' => 'Mobile banking charge',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $sale = $this->sale->fresh();

    expect((float) $sale->other_fee)->toBe(20.0)
        ->and((float) $sale->total_payable)->toBe(520.0)
        ->and((float) $sale->due_amount)->toBe(220.0)
        ->and(dueSaleReceivedAmount($sale))->toBe(300.0);

    $payment = Payment::where('sales_id', $this->sale->id)->latest('id')->firstOrFail();

    expect((float) $payment->received_amount)->toBe(100.0)
        ->and($payment->payment_method)->toBe('Bkash')
        ->and($payment->transaction_id)->toBe('TRX-11223344')
        ->and($payment->remark)->toBe('Mobile banking charge');
});

it('subtracts pending BFTN from due without counting it as received', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'payment_method' => 'Bank Transfer',
            'is_bftn' => true,
            'transaction_id' => 'BFTN-112233',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $sale = $this->sale->fresh();
    $payment = Payment::where('sales_id', $sale->id)->latest('id')->firstOrFail();

    expect(dueSaleReceivedAmount($sale))->toBe(200.0)
        ->and((float) $sale->due_amount)->toBe(200.0)
        ->and($payment->settlement_status)->toBe('pending')
        ->and((float) $payment->received_amount)->toBe(100.0);
});

it('accumulates the other fee on top of the existing other fee', function () {
    $this->sale->update(['other_fee' => 30, 'total_payable' => 530, 'due_amount' => 330]);

    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 130,
            'other_fee' => 20,
            'payment_method' => 'Cash',
        ])
        ->assertOk();

    $sale = $this->sale->fresh();

    expect((float) $sale->other_fee)->toBe(50.0)
        ->and((float) $sale->total_payable)->toBe(550.0)
        ->and((float) $sale->due_amount)->toBe(220.0)
        ->and(dueSaleReceivedAmount($sale))->toBe(330.0);
});

it('clears the due amount when the other fee is collected in full', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 320,
            'other_fee' => 20,
            'payment_method' => 'Nagad',
            'transaction_id' => 'TRX-99887766',
        ])
        ->assertOk();

    $sale = $this->sale->fresh();

    expect((float) $sale->due_amount)->toBe(0.0)
        ->and((float) $sale->total_payable)->toBe(520.0)
        ->and(dueSaleReceivedAmount($sale))->toBe(520.0);
});

it('keeps the existing totals when no other fee is provided', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 300,
            'payment_method' => 'Cash',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $sale = $this->sale->fresh();

    expect((float) $sale->other_fee)->toBe(0.0)
        ->and((float) $sale->total_payable)->toBe(500.0)
        ->and((float) $sale->due_amount)->toBe(0.0)
        ->and(dueSaleReceivedAmount($sale))->toBe(500.0);
});

it('rejects a negative other fee', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'other_fee' => -5,
            'payment_method' => 'Cash',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('other_fee');

    expect((float) $this->sale->fresh()->other_fee)->toBe(0.0)
        ->and(Payment::where('sales_id', $this->sale->id)->count())->toBe(1);
});

it('stores the transaction id with the payment timestamps for mobile banking payments', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'payment_method' => 'Bkash',
            'transaction_id' => 'TRX-44556677',
        ])
        ->assertOk();

    $payment = Payment::where('sales_id', $this->sale->id)->latest('id')->firstOrFail();

    expect($payment->transaction_id)->toBe('TRX-44556677')
        ->and($payment->paid_date)->not->toBeNull()
        ->and($payment->payment_datetime)->not->toBeNull();
});

it('requires a transaction id for mobile banking and bank payments', function (string $paymentMethod) {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 50,
            'payment_method' => $paymentMethod,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transaction_id');

    expect(Payment::where('sales_id', $this->sale->id)->count())->toBe(1);
})->with(['Bkash', 'Nagad', 'Bank Transfer']);

it('does not require a transaction id for cash payments', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 50,
            'payment_method' => 'Cash',
        ])
        ->assertOk();

    expect(Payment::where('sales_id', $this->sale->id)->latest('id')->firstOrFail()->transaction_id)->toBeNull();
});

it('reduces the total payable and the due amount when a discount is given while collecting the due', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 250,
            'discount_amount' => 50,
            'payment_method' => 'Cash',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $sale = $this->sale->fresh();

    expect((float) $sale->discount_amount)->toBe(50.0)
        ->and((float) $sale->total_payable)->toBe(450.0)
        ->and(dueSaleReceivedAmount($sale))->toBe(450.0)
        ->and((float) $sale->due_amount)->toBe(0.0);
});

it('applies the other fee and the discount together when collecting the due', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'other_fee' => 20,
            'discount_amount' => 50,
            'payment_method' => 'Cash',
        ])
        ->assertOk();

    $sale = $this->sale->fresh();

    expect((float) $sale->other_fee)->toBe(20.0)
        ->and((float) $sale->discount_amount)->toBe(50.0)
        ->and((float) $sale->total_payable)->toBe(470.0)
        ->and(dueSaleReceivedAmount($sale))->toBe(300.0)
        ->and((float) $sale->due_amount)->toBe(170.0);
});

it('rejects a discount larger than the total due amount', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 10,
            'discount_amount' => 400,
            'payment_method' => 'Cash',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('discount_amount');

    $sale = $this->sale->fresh();

    expect((float) $sale->discount_amount)->toBe(0.0)
        ->and((float) $sale->total_payable)->toBe(500.0)
        ->and((float) $sale->due_amount)->toBe(300.0)
        ->and(Payment::where('sales_id', $this->sale->id)->count())->toBe(1);
});

it('counts the other fee as part of the discountable due amount', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'other_fee' => 20,
            'discount_amount' => 321,
            'payment_method' => 'Cash',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('discount_amount');
});

it('shows the other fee, discount, total due, transaction id and proof fields in the paid due modal', function () {
    $response = $this->actingAs($this->admin)
        ->get('/sales/status/pending');

    $response
        ->assertOk()
        ->assertSee('id="otherFeeInput"', false)
        ->assertSee('id="discountInput"', false)
        ->assertSee('id="totalDueInput"', false)
        ->assertSee('id="dueAmountInput"', false)
        ->assertSee('id="transactionIdWrapper"', false)
        ->assertSee('id="transactionIdInput"', false)
        ->assertSee('id="paymentProofInput"', false);

    $script = file_get_contents(resource_path('js/components/due-payment.js'));

    expect($script)
        ->toContain("formData.append('other_fee', otherFee)")
        ->toContain("formData.append('discount_amount', discount)")
        ->toContain("formData.append('transaction_id', transactionId)")
        ->toContain("formData.append('payment_proof', proofFile)")
        ->toContain("otherFeeInput.value = '0.00'")
        ->toContain("discountInput.value = '0.00'");
});
