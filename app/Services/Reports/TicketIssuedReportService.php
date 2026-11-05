<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\ShipTicketSale;
use App\Models\VerifyTracker;
use App\Services\Sales\SaleFinancialService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketIssuedReportService
{
    public function __construct(private readonly SaleFinancialService $saleFinancials) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataTable(array $filters): array
    {
        $withoutSearch = $filters;
        unset($withoutSearch['search']);
        $recordsTotal = $this->filteredQuery($withoutSearch)->count();
        $query = $this->filteredQuery($filters);
        $recordsFiltered = (clone $query)->count();
        $totals = $this->totals(clone $query);

        $matchingHistory = VerifyTracker::query()
            ->select('verify_tracker.id')
            ->whereColumn('verify_tracker.ticket_id', 'ship_ticket_sales.id');
        $this->applyIssueHistoryFilters($matchingHistory, $filters);
        $matchingIssueDate = (clone $matchingHistory)->select('verify_tracker.created_at')
            ->orderBy('created_at')->orderBy('id')->limit(1);
        $direction = ($filters['issued_date_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $sales = $query->select('ship_ticket_sales.*')
            ->selectSub($matchingHistory->orderBy('created_at')->orderBy('id')->limit(1), 'matching_issue_id')
            ->selectSub($matchingIssueDate, 'matching_issue_date')
            ->with([
                'ships',
                'companies',
                'categories',
                'payments',
                'refunds',
                'verifyby' => fn (HasMany $history): HasMany => $history->orderBy('created_at')->orderBy('id'),
                'verifyby.verifiedByUser:id,name',
            ])
            ->orderBy('matching_issue_date', $direction)
            ->orderBy('ship_ticket_sales.id', $direction)
            ->skip(max(0, (int) ($filters['start'] ?? 0)))
            ->take(min(100, max(1, (int) ($filters['length'] ?? 10))))
            ->get();

        return [
            'draw' => (int) ($filters['draw'] ?? 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $sales->map(fn (ShipTicketSale $sale): array => $this->formatSale($sale))->all(),
            'totals' => $totals,
        ];
    }

    /**
     * @param  Builder<ShipTicketSale>  $query
     * @return array{total_sales: int, total_tickets: int, total_ticket_value: float, total_discount_amount: float, total_other_fee: float, total_payable: float, total_received_amount: float, total_due_amount: float}
     */
    private function totals(Builder $query): array
    {
        $totals = [
            'total_sales' => 0,
            'total_tickets' => 0,
            'total_ticket_value' => 0.0,
            'total_discount_amount' => 0.0,
            'total_other_fee' => 0.0,
            'total_payable' => 0.0,
            'total_received_amount' => 0.0,
            'total_due_amount' => 0.0,
        ];

        $query->with(['categories', 'payments', 'refunds'])
            ->chunkById(250, function (Collection $sales) use (&$totals): void {
                foreach ($sales as $sale) {
                    $summary = $this->saleFinancials->currentSummary($sale);
                    $totals['total_sales']++;
                    $totals['total_tickets'] += $sale->ticket_count;
                    $totals['total_ticket_value'] += $summary['ticket_value'];
                    $totals['total_discount_amount'] += (float) $sale->discount_amount;
                    $totals['total_other_fee'] += (float) $sale->other_fee;
                    $totals['total_payable'] += $summary['total_payable'];
                    $totals['total_received_amount'] += $summary['received_amount'];
                    $totals['total_due_amount'] += $summary['due_amount'];
                }
            });

        foreach ($totals as $key => $value) {
            if (! in_array($key, ['total_sales', 'total_tickets'], true)) {
                $totals[$key] = round($value, 2);
            }
        }

        return $totals;
    }

    /** @return array<string, mixed> */
    private function formatSale(ShipTicketSale $sale): array
    {
        $summary = $this->saleFinancials->currentSummary($sale);
        $issue = $sale->verifyby->firstWhere('id', (int) $sale->matching_issue_id);

        return [
            'id' => $sale->id,
            'customer_name' => $sale->customer_name,
            'customer_mobile' => $sale->customer_mobile,
            'ship_name' => $sale->ships?->name,
            'company_name' => $sale->companies?->name,
            'journey_date' => $sale->journey_date,
            'return_date' => $sale->return_date,
            'created_at' => $sale->created_at?->toIso8601String(),
            'ticket_count' => $sale->ticket_count,
            'issued_at' => $issue?->created_at?->toIso8601String(),
            'issued_by' => $issue?->verifiedByUser?->name,
            'status' => $sale->status,
            'status_label' => SaleStatus::tryFrom($sale->status)?->label() ?? $sale->status,
            'ticket_value' => $summary['ticket_value'],
            'other_fee' => (float) $sale->other_fee,
            'discount_amount' => (float) $sale->discount_amount,
            'total_payable' => $summary['total_payable'],
            'received_amount' => $summary['received_amount'],
            'due_amount' => $summary['due_amount'],
            'verification_timeline' => $sale->verifyby->map(fn (VerifyTracker $history): array => [
                'status' => SaleStatus::tryFrom($history->name)?->label() ?? $history->name,
                'verified_by' => $history->verifiedByUser?->name,
                'verified_at' => $history->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * @param array{
     *     ticket_issued_from?: string|null,
     *     ticket_issued_to?: string|null,
     *     ship_ids?: list<int|string>|null,
     *     search?: array{value?: string|null}
     * } $filters
     * @return Builder<ShipTicketSale>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = ShipTicketSale::query()
            ->whereHas('verifyby', function (Builder $history) use ($filters): void {
                $this->applyIssueHistoryFilters($history, $filters);
            });

        if (! empty($filters['ship_ids'])) {
            $query->whereIn('ship_ticket_sales.ship_id', $filters['ship_ids']);
        }

        $search = trim((string) ($filters['search']['value'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $sales) use ($search): void {
                $sales->where('ship_ticket_sales.customer_name', 'like', "%{$search}%")
                    ->orWhere('ship_ticket_sales.customer_mobile', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $sales->orWhere('ship_ticket_sales.id', $search);
                }
            });
        }

        return $query;
    }

    /**
     * @param  array{ticket_issued_from?: string|null, ticket_issued_to?: string|null}  $filters
     */
    private function applyIssueHistoryFilters(Builder $history, array $filters): void
    {
        $history->where('verify_tracker.name', SaleStatus::TicketIssued->value);

        if (! empty($filters['ticket_issued_from'])) {
            $history->where(
                'verify_tracker.created_at',
                '>=',
                CarbonImmutable::parse($filters['ticket_issued_from'], 'Asia/Dhaka')
                    ->startOfDay()
                    ->setTimezone(config('app.timezone')),
            );
        }

        if (! empty($filters['ticket_issued_to'])) {
            $history->where(
                'verify_tracker.created_at',
                '<',
                CarbonImmutable::parse($filters['ticket_issued_to'], 'Asia/Dhaka')
                    ->addDay()
                    ->startOfDay()
                    ->setTimezone(config('app.timezone')),
            );
        }
    }
}
