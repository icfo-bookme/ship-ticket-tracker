<?php

use App\Models\AuditLog;
use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->index(['sale_id', 'created_at', 'id'], 'audit_logs_sale_time_index');
            $table->index(['auditable_type', 'created_at', 'id', 'event'], 'audit_logs_type_time_index');
        });

        $saleType = (new ShipTicketSale)->getMorphClass();
        AuditLog::query()->select(['id', 'auditable_type', 'auditable_id', 'context'])
            ->chunkById(500, function (Collection $logs) use ($saleType): void {
                $idsBySale = [];

                foreach ($logs as $log) {
                    $saleId = $log->auditable_type === $saleType ? $log->auditable_id : ($log->context['sale_id'] ?? null);

                    if (is_numeric($saleId) && (int) $saleId > 0) {
                        $idsBySale[(int) $saleId][] = $log->id;
                    }
                }

                foreach ($idsBySale as $saleId => $ids) {
                    AuditLog::query()->whereIn('id', $ids)->update(['sale_id' => $saleId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_sale_time_index');
            $table->dropIndex('audit_logs_type_time_index');
            $table->dropColumn('sale_id');
        });
    }
};
