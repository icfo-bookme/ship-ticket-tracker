<?php

use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->ship = Ship::create(['name' => 'MV Due Ship', 'status' => 1]);

    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Due Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'total_payable' => 500,
        'received_amount' => 200,
        'due_amount' => 300,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'pending',
    ]);
});

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
        ->and((float) $sale->received_amount)->toBe(300.0);

    $payment = Payment::where('sales_id', $this->sale->id)->sole();

    expect((float) $payment->received_amount)->toBe(100.0)
        ->and($payment->payment_method)->toBe('Bkash')
        ->and($payment->transaction_id)->toBe('TRX-11223344')
        ->and($payment->remark)->toBe('Mobile banking charge');
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
        ->and((float) $sale->received_amount)->toBe(330.0);
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
        ->and((float) $sale->received_amount)->toBe(520.0);
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
        ->and((float) $sale->received_amount)->toBe(500.0);
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
        ->and(Payment::where('sales_id', $this->sale->id)->count())->toBe(0);
});

it('stores the transaction id with the payment timestamps for mobile banking payments', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 100,
            'payment_method' => 'Bkash',
            'transaction_id' => 'TRX-44556677',
        ])
        ->assertOk();

    $payment = Payment::where('sales_id', $this->sale->id)->sole();

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

    expect(Payment::where('sales_id', $this->sale->id)->count())->toBe(0);
})->with(['Bkash', 'Nagad', 'Bank Transfer']);

it('does not require a transaction id for cash payments', function () {
    $this->actingAs($this->admin)
        ->postJson('/partial/paid/'.$this->sale->id, [
            'paid_amount' => 50,
            'payment_method' => 'Cash',
        ])
        ->assertOk();

    expect(Payment::where('sales_id', $this->sale->id)->sole()->transaction_id)->toBeNull();
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
        ->and((float) $sale->received_amount)->toBe(450.0)
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
        ->and((float) $sale->received_amount)->toBe(300.0)
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
        ->and(Payment::where('sales_id', $this->sale->id)->count())->toBe(0);
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
    $this->actingAs($this->admin)
        ->get('/sales/status/pending')
        ->assertOk()
        ->assertSee('id="otherFeeInput"', false)
        ->assertSee('id="discountInput"', false)
        ->assertSee('id="totalDueInput"', false)
        ->assertSee('id="dueAmountInput"', false)
        ->assertSee('id="transactionIdWrapper"', false)
        ->assertSee('id="transactionIdInput"', false)
        ->assertSee('id="paymentProofInput"', false)
        ->assertSee("formData.append('other_fee', otherFee)", false)
        ->assertSee("formData.append('discount_amount', discount)", false)
        ->assertSee("formData.append('transaction_id', transactionId)", false)
        ->assertSee("formData.append('payment_proof', proofFile)", false)
        ->assertSee("otherFeeInput.value = '0.00'", false)
        ->assertSee("discountInput.value = '0.00'", false);
});
