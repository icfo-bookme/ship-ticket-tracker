<?php

use App\Models\AuditLog;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Support\Facades\Schema;

it('backfills indexed sale references without changing historical snapshots', function () {
    $saleLog = AuditLog::factory()->create([
        'auditable_type' => (new ShipTicketSale)->getMorphClass(), 'auditable_id' => 77,
    ]);
    $refundLog = AuditLog::factory()->create([
        'auditable_type' => (new Refund)->getMorphClass(), 'auditable_id' => 99,
        'context' => ['sale_id' => 77],
    ]);
    $unrelated = AuditLog::factory()->create();
    $oldValues = $saleLog->old_values;
    $createdAt = $saleLog->created_at;
    $migration = require database_path('migrations/2026_10_27_094112_add_sale_lookup_and_ordering_indexes_to_audit_logs_table.php');
    $migration->down();

    expect(Schema::hasColumn('audit_logs', 'sale_id'))->toBeFalse();

    $migration->up();

    expect($saleLog->fresh()->sale_id)->toBe(77)
        ->and($refundLog->fresh()->sale_id)->toBe(77)
        ->and($unrelated->fresh()->sale_id)->toBeNull()
        ->and($saleLog->fresh()->old_values)->toBe($oldValues)
        ->and($saleLog->fresh()->created_at->equalTo($createdAt))->toBeTrue()
        ->and(AuditLog::query()->count())->toBe(3);

    $indexes = collect(Schema::getIndexes('audit_logs'))->keyBy('name');
    expect($indexes['audit_logs_sale_time_index']['columns'])->toBe(['sale_id', 'created_at', 'id'])
        ->and($indexes['audit_logs_type_time_index']['columns'])->toBe(['auditable_type', 'created_at', 'id', 'event']);
});

it('can use the lookup and ordering indexes for paginated queries', function () {
    $connection = (new AuditLog)->getConnection();
    $queries = [
        'audit_logs_sale_time_index' => AuditLog::query()->where('sale_id', 77),
        'audit_logs_type_time_index' => AuditLog::query()->where('auditable_type', (new ShipTicketSale)->getMorphClass())
            ->whereIn('event', ['created', 'updated']),
    ];

    foreach ($queries as $index => $query) {
        $query->orderByDesc('created_at')->orderByDesc('id')->limit(20);
        $plan = $connection->select('EXPLAIN QUERY PLAN '.$query->toSql(), $query->getBindings());
        $details = json_encode($plan, JSON_THROW_ON_ERROR);

        expect($details)->toContain($index)->not->toContain('USE TEMP B-TREE');
    }
});
