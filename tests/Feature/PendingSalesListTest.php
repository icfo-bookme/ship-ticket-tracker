<?php

use App\Models\Company;
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

    $this->ship = Ship::create(['name' => 'MV Pending Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Pending Company', 'status' => 1]);

    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Pending Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'company_id' => $this->company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 300,
        'other_fee' => 0,
        'total_payable' => 300,
        'received_amount' => 300,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'pending',
    ]);

    Payment::create([
        'sales_id' => $this->sale->id,
        'payment_method' => 'Bkash',
        'received_amount' => 300,
        'paid_date' => now()->toDateString(),
        'transaction_id' => 'TRX-99887766',
    ]);
});

it('shows the payment transaction id in red before the total received amount on the pending list', function () {
    $html = $this->actingAs($this->admin)
        ->get('/sales/status/pending')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('transactionIdBadge bg-red-500 text-white');

    $theadStart = strpos($html, '<thead');
    $thead = substr($html, $theadStart, strpos($html, '</thead>') - $theadStart);

    expect(strpos($thead, 'Transaction ID'))->toBeLessThan(strpos($thead, 'Total Received Amount'))
        ->and(strpos($thead, 'Transaction ID'))->not->toBeFalse();
});

it('keeps the transaction id column out of the other status lists', function () {
    $html = $this->actingAs($this->admin)
        ->get('/sales/status/ticket-issued')
        ->assertOk()
        ->getContent();

    $theadStart = strpos($html, '<thead');
    $thead = substr($html, $theadStart, strpos($html, '</thead>') - $theadStart);

    expect($thead)->not->toContain('Transaction ID');
});

it('returns the payment transaction id in the pending sales payload', function () {
    $this->actingAs($this->admin)
        ->getJson('/sales/pending?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonPath('data.0.payments.0.transaction_id', 'TRX-99887766');
});

it('spaces out multiple transaction id badges on the pending list', function () {
    $this->sale->payments()->create([
        'payment_method' => 'Nagad',
        'received_amount' => 50,
        'transaction_id' => 'TRX-11223344',
    ]);

    $this->actingAs($this->admin)
        ->getJson('/sales/pending?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonCount(2, 'data.0.payments')
        ->assertJsonPath('data.0.payments.1.transaction_id', 'TRX-11223344');

    $html = $this->actingAs($this->admin)
        ->get('/sales/status/pending')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('flex flex-col items-center gap-1');
});

it('hides the remark columns from the sales table', function () {
    $html = $this->actingAs($this->admin)
        ->get('/sales/status/pending')
        ->assertOk()
        ->getContent();

    $theadStart = strpos($html, '<thead');
    $thead = substr($html, $theadStart, strpos($html, '</thead>') - $theadStart);

    expect($thead)->not->toContain('Remark 1')
        ->and($thead)->not->toContain('Remark 2');
});

it('shows one small thumbnail per uploaded payment proof on the pending list', function () {
    $this->sale->payments()->first()->update(['payment_proof' => 'payment-proofs/pending-1.jpg']);

    $this->sale->payments()->create([
        'payment_method' => 'Nagad',
        'received_amount' => 50,
        'payment_proof' => 'payment-proofs/pending-2.jpg',
    ]);

    $html = $this->actingAs($this->admin)
        ->get('/sales/status/pending')
        ->assertOk()
        ->getContent();

    $theadStart = strpos($html, '<thead');
    $thead = substr($html, $theadStart, strpos($html, '</thead>') - $theadStart);

    expect($thead)->toContain('Payment Proof')
        ->and($html)->toContain('renderPaymentProofs')
        ->and($html)->toContain('paymentProofBtn')
        ->and($html)->toContain('/payments/${payment.id}/proof')
        ->and($html)->toContain('h-10 w-10 object-cover')
        ->and($html)->toContain('flex flex-wrap items-center justify-center gap-1')
        ->and($html)->toContain('id="proofImageModal"')
        ->and($html)->toContain('id="proofImageModalImg"')
        ->and($html)->toContain('openPaymentProof');
});

it('keeps the payment proof column out of the other status lists', function () {
    $html = $this->actingAs($this->admin)
        ->get('/sales/status/ticket-issued')
        ->assertOk()
        ->getContent();

    $theadStart = strpos($html, '<thead');
    $thead = substr($html, $theadStart, strpos($html, '</thead>') - $theadStart);

    expect($thead)->not->toContain('Payment Proof')
        ->and($thead)->not->toContain('Remark 1')
        ->and($thead)->not->toContain('Remark 2');
});
