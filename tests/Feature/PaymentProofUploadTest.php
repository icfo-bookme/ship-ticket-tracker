<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->ship = Ship::create(['name' => 'MV Proof Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Proof Company', 'status' => 1]);
});

function saleWithProofPayment(Ship $ship, Company $company): ShipTicketSale
{
    $sale = ShipTicketSale::create([
        'customer_name' => 'Proof Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 200,
        'due_amount' => 300,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'pending',
    ]);

    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bkash',
        'received_amount' => 200,
        'transaction_id' => 'TRX-KEEP-1',
        'payment_datetime' => '2026-09-23 10:00:00',
        'payment_proof' => 'payment-proofs/existing-proof.jpg',
    ]);

    return $sale;
}

function proofSalePayload(Ship $ship, Company $company, User $user, array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'New Proof Customer',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'nid' => null,
        'email' => null,
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'address' => 'Dhaka',
        'journey_date' => '2026-10-01',
        'date_of_birth' => null,
        'return_date' => null,
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'number_of_ticket' => 1,
        'ticket_category' => null,
        'bftn_status' => null,
        'company_id' => $company->id,
        'issued_date' => '2026-09-23',
        'status' => 'pending',
        'sold_by' => (string) $user->id,
        'remark1' => null,
        'remark2' => null,
    ], $overrides);
}

function proofUpdatePayload(Ship $ship, Company $company, User $user, array $overrides = []): array
{
    return proofSalePayload($ship, $company, $user, array_merge([
        'received_amount' => 200,
        'due_amount' => 300,
        'payments' => [],
    ], $overrides));
}

it('stores the payment proof uploaded from the create form', function () {
    $this->actingAs($this->admin)
        ->post('/ship-ticket-sales', proofSalePayload($this->ship, $this->company, $this->admin, [
            'payment_methods' => [
                [
                    'method' => 'Bkash',
                    'amount' => 500,
                    'paid_date' => '2026-09-23',
                    'transaction_id' => 'TRX-PROOF-1',
                    'proof_file' => UploadedFile::fake()->image('bkash-proof.jpg'),
                ],
            ],
        ]))
        ->assertRedirect(route('ship-ticket-sales.create'));

    $payment = Payment::latest('id')->first();

    expect($payment->transaction_id)->toBe('TRX-PROOF-1')
        ->and($payment->payment_proof)->not->toBeNull();

    Storage::disk('local')->assertExists($payment->payment_proof);
});

it('rejects a payment proof that is not an image or pdf', function () {
    $this->actingAs($this->admin)
        ->post('/ship-ticket-sales', proofSalePayload($this->ship, $this->company, $this->admin, [
            'payment_methods' => [
                [
                    'method' => 'Bkash',
                    'amount' => 500,
                    'proof_file' => UploadedFile::fake()->create('notes.txt', 5),
                ],
            ],
        ]))
        ->assertSessionHasErrors('payment_methods.0.proof_file');

    expect(Payment::count())->toBe(0);
});

it('rejects a payment proof larger than five megabytes', function () {
    $this->actingAs($this->admin)
        ->post('/ship-ticket-sales', proofSalePayload($this->ship, $this->company, $this->admin, [
            'payment_methods' => [
                [
                    'method' => 'Bkash',
                    'amount' => 500,
                    'proof_file' => UploadedFile::fake()->image('huge.jpg')->size(6000),
                ],
            ],
        ]))
        ->assertSessionHasErrors('payment_methods.0.proof_file');

    expect(Payment::count())->toBe(0);
});

it('keeps the payment proof, transaction id and datetime when the sale is updated', function () {
    $sale = saleWithProofPayment($this->ship, $this->company);
    Storage::disk('local')->put('payment-proofs/existing-proof.jpg', 'proof-bytes');

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$sale->id, proofUpdatePayload($this->ship, $this->company, $this->admin, [
            'payments' => [
                [
                    'payment_method' => 'Bkash',
                    'received_amount' => 200,
                    'paid_date' => '2026-09-23',
                    'transaction_id' => 'TRX-KEEP-1',
                    'payment_datetime' => '2026-09-23T10:00',
                    'payment_proof' => 'payment-proofs/existing-proof.jpg',
                    'remark' => 'kept',
                ],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $payment = Payment::where('sales_id', $sale->id)->sole();

    expect($payment->payment_proof)->toBe('payment-proofs/existing-proof.jpg')
        ->and($payment->transaction_id)->toBe('TRX-KEEP-1')
        ->and($payment->payment_datetime)->not->toBeNull()
        ->and($payment->remark)->toBe('kept');

    Storage::disk('local')->assertExists('payment-proofs/existing-proof.jpg');
});

it('replaces the stored proof and removes the old file when a new one is uploaded', function () {
    $sale = saleWithProofPayment($this->ship, $this->company);
    Storage::disk('local')->put('payment-proofs/existing-proof.jpg', 'old-bytes');

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$sale->id, proofUpdatePayload($this->ship, $this->company, $this->admin, [
            'payments' => [
                [
                    'payment_method' => 'Bkash',
                    'received_amount' => 200,
                    'paid_date' => '2026-09-23',
                    'payment_proof' => 'payment-proofs/existing-proof.jpg',
                    'proof_file' => UploadedFile::fake()->image('replacement.png'),
                ],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $payment = Payment::where('sales_id', $sale->id)->sole();

    expect($payment->payment_proof)->not->toBe('payment-proofs/existing-proof.jpg');

    Storage::disk('local')->assertMissing('payment-proofs/existing-proof.jpg');
    Storage::disk('local')->assertExists($payment->payment_proof);
});

it('deletes the proof file when its payment record is removed', function () {
    $sale = saleWithProofPayment($this->ship, $this->company);
    Storage::disk('local')->put('payment-proofs/existing-proof.jpg', 'old-bytes');

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$sale->id, proofUpdatePayload($this->ship, $this->company, $this->admin, [
            'payments' => [],
        ]))
        ->assertSessionHasNoErrors();

    expect(Payment::where('sales_id', $sale->id)->count())->toBe(0);

    Storage::disk('local')->assertMissing('payment-proofs/existing-proof.jpg');
});

it('stores the payment proof uploaded from the due payment modal without a transaction id', function () {
    $sale = saleWithProofPayment($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->post('/partial/paid/'.$sale->id, [
            'paid_amount' => 50,
            'payment_method' => 'Bkash',
            'payment_proof' => UploadedFile::fake()->image('due-proof.jpg'),
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $payment = Payment::where('sales_id', $sale->id)->latest('id')->first();

    expect($payment->transaction_id)->toBeNull()
        ->and($payment->payment_proof)->not->toBeNull();

    Storage::disk('local')->assertExists($payment->payment_proof);
});

it('serves the stored payment proof through the protected route', function () {
    $sale = saleWithProofPayment($this->ship, $this->company);
    Storage::disk('local')->put('payment-proofs/existing-proof.jpg', 'proof-bytes');

    $payment = Payment::where('sales_id', $sale->id)->sole();

    $this->actingAs($this->admin)
        ->get('/payments/'.$payment->id.'/proof')
        ->assertOk();

    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 10,
    ]);

    $paymentWithoutProof = Payment::where('sales_id', $sale->id)->latest('id')->first();

    $this->actingAs($this->admin)
        ->get('/payments/'.$paymentWithoutProof->id.'/proof')
        ->assertNotFound();
});

it('exposes the proof fields on the create, edit and due payment forms', function () {
    $sale = saleWithProofPayment($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/create')
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false);

    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/'.$sale->id.'/edit')
        ->assertOk()
        ->assertSee('payments[0][proof_file]', false)
        ->assertSee('View uploaded proof');

    $this->actingAs($this->admin)
        ->get('/sales/status/pending')
        ->assertOk()
        ->assertSee('id="paymentProofInput"', false);
});
