<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('preserves audit history when the actor is deleted', function () {
    $user = User::factory()->create(['name' => 'Audit Actor']);
    $connection = $user->getConnection();
    $id = $connection->table('audit_logs')->insertGetId([
        'auditable_type' => 'sale',
        'auditable_id' => 123,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'event' => 'updated',
        'old_values' => json_encode(['discount_amount' => 100], JSON_THROW_ON_ERROR),
        'new_values' => json_encode(['discount_amount' => 200], JSON_THROW_ON_ERROR),
    ]);

    $user->delete();
    $log = $connection->table('audit_logs')->find($id);

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBeNull()
        ->and($log->user_name)->toBe('Audit Actor')
        ->and($log->created_at)->not->toBeNull()
        ->and(json_decode($log->old_values, true))->toBe(['discount_amount' => 100])
        ->and(json_decode($log->new_values, true))->toBe(['discount_amount' => 200])
        ->and(Schema::hasColumn('audit_logs', 'updated_at'))->toBeFalse();
});

it('can roll back and recreate the audit logs table', function () {
    $migration = require database_path('migrations/2026_10_27_075609_create_audit_logs_table.php');
    $migration->down();

    expect(Schema::hasTable('audit_logs'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumns('audit_logs', [
        'id', 'auditable_type', 'auditable_id', 'user_id', 'user_name',
        'event', 'old_values', 'new_values', 'description', 'created_at',
    ]))->toBeTrue();
});
