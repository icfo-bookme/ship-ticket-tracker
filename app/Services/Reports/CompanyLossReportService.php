<?php

namespace App\Services\Reports;

use App\Enums\RefundStatus;
use App\Models\CompanyErrorRefund;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class CompanyLossReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataTable(array $filters): array
    {
        $query = CompanyErrorRefund::query()->whereHas('refund', function (Builder $refund) use ($filters): void {
            $refund->where('status', '!=', RefundStatus::Cancelled->value);

            if (! empty($filters['status'])) {
                $refund->where('status', $filters['status']);
            }

            foreach (['ship_id', 'company_id'] as $field) {
                if (! empty($filters[$field])) {
                    $refund->whereHas('sale', fn (Builder $sale): Builder => $sale->where($field, $filters[$field]));
                }
            }

            if (! empty($filters['requested_from'])) {
                $refund->where('requested_at', '>=', CarbonImmutable::parse($filters['requested_from'], 'Asia/Dhaka')->startOfDay()->setTimezone(config('app.timezone')));
            }

            if (! empty($filters['requested_to'])) {
                $refund->where('requested_at', '<', CarbonImmutable::parse($filters['requested_to'], 'Asia/Dhaka')->addDay()->startOfDay()->setTimezone(config('app.timezone')));
            }
        });

        if (! empty($filters['loss_type'])) {
            $query->where('loss_type', $filters['loss_type']);
        }

        $recordsTotal = (clone $query)->count();
        $search = trim((string) ($filters['search']['value'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $loss) use ($search): void {
                $loss->where('reason', 'like', "%{$search}%")
                    ->orWhere('refund_id', $search)
                    ->orWhereHas('refund', fn (Builder $refund): Builder => $refund->where('sales_id', $search))
                    ->orWhereHas('refund.sale', fn (Builder $sale): Builder => $sale
                        ->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_mobile', 'like', "%{$search}%"));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $totalLoss = (float) (clone $query)->sum('loss_amount');
        $completedLoss = (float) (clone $query)->whereHas('refund', fn (Builder $refund): Builder => $refund->where('status', RefundStatus::Completed->value))->sum('loss_amount');

        $losses = $query->with(['refund.sale.ships', 'refund.sale.companies', 'creator:id,name'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->skip((int) ($filters['start'] ?? 0))
            ->take((int) ($filters['length'] ?? 10))->get();

        return [
            'draw' => (int) ($filters['draw'] ?? 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'totals' => [
                'count' => $recordsFiltered,
                'total_loss' => round($totalLoss, 2),
                'completed_loss' => round($completedLoss, 2),
                'pending_loss' => round($totalLoss - $completedLoss, 2),
            ],
            'data' => $losses->map(fn (CompanyErrorRefund $loss): array => [
                'id' => $loss->id,
                'refund_id' => $loss->refund_id,
                'sale_id' => $loss->refund->sales_id,
                'customer_name' => $loss->refund->sale?->customer_name ?? 'N/A',
                'ship_name' => $loss->refund->sale?->ships?->name ?? 'N/A',
                'company_name' => $loss->refund->sale?->companies?->name ?? 'N/A',
                'status' => $loss->refund->status,
                'requested_at' => $loss->refund->requested_at?->timezone('Asia/Dhaka')->format('Y-m-d H:i'),
                'gross_refund_amount' => $loss->refund->gross_refund_amount,
                'loss_type' => $loss->loss_type,
                'loss_percent' => $loss->loss_percent,
                'loss_amount' => $loss->loss_amount,
                'reason' => $loss->reason,
                'created_by' => $loss->creator?->name ?? 'System',
            ])->all(),
        ];
    }
}
