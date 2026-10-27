<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\AuditLogService;

beforeEach(function () {
    $ship = Ship::create(['name' => 'Audit Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Audit Company', 'status' => 1]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Audit Customer', 'customer_mobile' => '01712345678',
        'sales_source' => 'Direct', 'ship_id' => $ship->id, 'company_id' => $company->id,
        'journey_date' => '2026-10-28', 'issued_date' => '2026-10-27',
        'discount_amount' => 100, 'total_payable' => 500, 'status' => 'pending',
    ]);
    $this->service = app(AuditLogService::class);
});

it('records only changed allowed fields with the authenticated actor', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $log = $this->sale->getConnection()->transaction(fn () => $this->service->record(
        $this->sale, 'updated',
        ['discount_amount' => 100, 'customer_name' => 'Same', 'password' => 'secret'],
        ['discount_amount' => 200, 'customer_name' => 'Same', 'password' => 'changed'],
    ));

    expect($log->fresh()->old_values)->toBe(['discount_amount' => '100.00'])
        ->and($log->new_values)->toBe(['discount_amount' => '200.00'])
        ->and($log->user->is($actor))->toBeTrue()
        ->and($log->user_name)->toBe($actor->name)
        ->and($log->sale_id)->toBe($this->sale->id)
        ->and($log->auditable->is($this->sale))->toBeTrue()
        ->and($log->created_at)->not->toBeNull();
});

it('does not log equivalent amounts dates and quantities', function () {
    $log = $this->service->record(
        $this->sale, 'updated',
        ['discount_amount' => 100, 'journey_date' => '2026-10-28', 'collect_from_office' => false],
        ['discount_amount' => '100.00', 'journey_date' => '2026-10-28 00:00:00', 'collect_from_office' => '0'],
    );

    expect($log)->toBeNull()->and(AuditLog::query()->count())->toBe(0);
});

it('preserves related names captured before a change', function () {
    $before = $this->service->snapshot($this->sale);
    $this->sale->ships()->first()->update(['name' => 'Renamed Ship']);
    $log = $this->sale->getConnection()->transaction(fn () => $this->service->record(
        $this->sale, 'updated', $before, $this->service->snapshot($this->sale),
    ));

    expect($log->old_values['ship_id']['name'])->toBe('Audit Ship')
        ->and($log->new_values['ship_id']['name'])->toBe('Renamed Ship');
});

it('allows system events without attributing them to the signed in user', function () {
    $this->actingAs(User::factory()->create());
    $log = $this->sale->getConnection()->transaction(fn () => $this->service->record(
        $this->sale, 'created', newValues: $this->service->snapshot($this->sale), system: true,
    ));

    expect($log->user_id)->toBeNull()->and($log->user_name)->toBeNull();
});

it('rolls back the sale change and log together on failure', function () {
    expect(function () {
        $this->sale->getConnection()->transaction(function () {
            $before = $this->service->snapshot($this->sale);
            $this->sale->update(['customer_name' => 'Changed']);
            $this->service->record($this->sale, 'updated', $before, $this->service->snapshot($this->sale));

            throw new RuntimeException('Simulated save failure');
        });
    })->toThrow(RuntimeException::class);

    expect($this->sale->fresh()->customer_name)->toBe('Audit Customer')
        ->and(AuditLog::query()->count())->toBe(0);
});

it('rejects unsupported events and subjects', function () {
    expect(fn () => $this->service->record($this->sale, 'unknown'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $this->service->snapshot(User::factory()->create()))->toThrow(InvalidArgumentException::class);
});

it('keeps the history linked to a soft deleted sale', function () {
    $log = $this->sale->getConnection()->transaction(fn () => $this->service->record(
        $this->sale, 'deleted', oldValues: $this->service->snapshot($this->sale),
    ));
    $this->sale->delete();

    expect($log->fresh()->auditable->trashed())->toBeTrue();
});
