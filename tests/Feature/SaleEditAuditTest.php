<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Sales\ShipTicketSaleService;

beforeEach(function () {
    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);
    $ship = Ship::create(['name' => 'Edit Audit Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Edit Audit Company', 'status' => 1]);
    $this->package = ShipPackage::create([
        'ship_id' => $ship->id, 'name' => 'Main Deck', 'price' => 500, 'round_trip_price' => 800,
    ]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Original Customer', 'customer_mobile' => '01712345678',
        'sales_source' => 'Direct', 'ship_id' => $ship->id, 'company_id' => $company->id,
        'journey_date' => '2026-10-28', 'return_date' => null, 'issued_date' => '2026-10-27',
        'collect_from_office' => false, 'discount_amount' => 0, 'other_fee' => 0,
        'total_payable' => 500, 'status' => 'pending', 'sold_by' => $this->actor->id,
    ]);
    $this->sale->categories()->create([
        'package_id' => $this->package->id, 'quantity' => 1, 'unit_price' => 500, 'type' => 'departure',
    ]);
    $this->data = [
        ...$this->sale->only([
            'customer_name', 'customer_mobile', 'ship_id', 'company_id', 'journey_date',
            'return_date', 'issued_date', 'sales_source', 'collect_from_office',
        ]),
        'email' => null, 'nid' => null, 'date_of_birth' => null,
        'remark1' => null, 'remark2' => null,
        'departure_quantity' => [$this->package->id => 1],
    ];
});

it('combines customer financial and category changes in one sale edit entry', function () {
    $data = [...$this->data,
        'customer_name' => 'Updated Customer', 'discount_amount' => 100,
        'departure_quantity' => [$this->package->id => 2],
    ];

    app(ShipTicketSaleService::class)->update($this->sale, $data, $data);
    $log = AuditLog::query()->sole();

    expect($log->event)->toBe('updated')
        ->and($log->user_id)->toBe($this->actor->id)
        ->and($log->auditable_id)->toBe($this->sale->id)
        ->and($log->old_values['customer_name'])->toBe('Original Customer')
        ->and($log->new_values['customer_name'])->toBe('Updated Customer')
        ->and($log->new_values['discount_amount'])->toBe('100.00')
        ->and($log->new_values['total_payable'])->toBe('900.00')
        ->and($log->old_values['ticket_categories'][0]['quantity'])->toBe(1)
        ->and($log->new_values['ticket_categories'][0]['quantity'])->toBe(2)
        ->and($log->new_values['ticket_categories'][0]['package_id']['name'])->toBe('Main Deck')
        ->and($log->new_values)->not->toHaveKey('sold_by');
});

it('does not log an unchanged edit even when category rows are recreated', function () {
    $categoryId = $this->sale->categories()->sole()->id;
    app(ShipTicketSaleService::class)->update($this->sale, $this->data, $this->data);

    expect($this->sale->categories()->sole()->id)->not->toBe($categoryId)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('captures related name snapshots when the ship changes', function () {
    $ship = Ship::create(['name' => 'Replacement Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $ship->id, 'name' => 'Replacement Deck', 'price' => 600,
    ]);
    $data = [...$this->data, 'ship_id' => $ship->id, 'departure_quantity' => [$package->id => 1]];
    app(ShipTicketSaleService::class)->update($this->sale, $data, $data);
    $log = AuditLog::query()->sole();
    $ship->update(['name' => 'Later Rename']);

    expect($log->old_values['ship_id']['name'])->toBe('Edit Audit Ship')
        ->and($log->fresh()->new_values['ship_id'])->toBe(['id' => $ship->id, 'name' => 'Replacement Ship']);
});

it('includes payment passenger and shipment edits without proof file contents', function () {
    $data = [...$this->data,
        'payments' => [['payment_method' => 'Cash', 'received_amount' => 100]],
        'co_passengers' => [['name' => 'Companion', 'nid' => '12345']],
        'shipment_id' => 'SHIP-001',
    ];
    app(ShipTicketSaleService::class)->update($this->sale, $data, $data);
    $log = AuditLog::query()->sole();

    expect($log->old_values['payments'])->toBe([])
        ->and($log->new_values['payments'][0]['received_amount'])->toBe('100.00')
        ->and($log->new_values['payments'][0])->not->toHaveKey('payment_proof')
        ->and($log->new_values['co_passengers'][0]['name'])->toBe('Companion')
        ->and($log->new_values['shipment_id'])->toBe('SHIP-001');

    app(ShipTicketSaleService::class)->update($this->sale->fresh(), $this->data, $this->data);
    $removed = AuditLog::query()->latest('id')->firstOrFail();

    expect($removed->new_values['payments'])->toBe([])
        ->and($removed->new_values['co_passengers'])->toBe([]);
});

it('rolls back the entire edit and audit entry if logging fails', function () {
    $original = new AuditLogService;
    $this->partialMock(AuditLogService::class, function ($mock) use ($original) {
        $mock->shouldReceive('record')->once()->andReturnUsing(function (...$arguments) use ($original) {
            $original->record(...$arguments);

            throw new RuntimeException('Audit write failed');
        });
    });
    $data = [...$this->data, 'customer_name' => 'Should Roll Back', 'departure_quantity' => [$this->package->id => 2]];

    expect(fn () => app(ShipTicketSaleService::class)->update($this->sale, $data, $data))
        ->toThrow(RuntimeException::class, 'Audit write failed');

    expect($this->sale->fresh()->customer_name)->toBe('Original Customer')
        ->and($this->sale->categories()->sole()->quantity)->toBe(1)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('leaves no audit entry for a rejected zero ticket edit', function () {
    $data = [...$this->data, 'departure_quantity' => [$this->package->id => 0]];

    expect(fn () => app(ShipTicketSaleService::class)->update($this->sale, $data, $data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(AuditLog::query()->count())->toBe(0)
        ->and($this->sale->fresh()->customer_name)->toBe('Original Customer');
});
