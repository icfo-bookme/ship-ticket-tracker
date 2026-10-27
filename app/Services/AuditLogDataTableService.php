<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ShipTicketSale;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogDataTableService
{
    /** @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: array<int, array<string, mixed>>} */
    public function dataTable(Request $request): array
    {
        $query = AuditLog::query()
            ->where('auditable_type', (new ShipTicketSale)->getMorphClass())
            ->whereIn('event', ['created', 'updated']);
        $total = $query->count();
        $search = $request->input('search.value');

        if (is_string($search) && trim($search) !== '') {
            $this->applySearch($query, trim($search));
        }

        $filtered = is_string($search) && trim($search) !== '' ? $query->count() : $total;
        $length = $request->integer('length', 10);
        $logs = $query->orderByDesc('created_at')->orderByDesc('id')
            ->select(['id', 'sale_id', 'auditable_id', 'created_at', 'user_name', 'event', 'old_values', 'new_values'])
            ->skip(max(0, $request->integer('start', 0)))
            ->take($length > 0 ? min($length, 100) : 10)
            ->get();

        return [
            'draw' => max(0, $request->integer('draw', 0)),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $logs->map(fn (AuditLog $log): array => $this->row($log))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function row(AuditLog $log): array
    {
        $fields = array_values(array_unique([...array_keys($log->old_values ?? []), ...array_keys($log->new_values ?? [])]));
        $labels = array_map(fn (string $field): string => $this->fieldLabel($field), $fields);

        return [
            'created_at' => $log->created_at->timezone(config('audit.timezone'))->format('d M Y, h:i A T'),
            'user_name' => $log->user_name ?? 'System / public booking',
            'sale_id' => $log->sale_id ?? $log->auditable_id,
            'summary' => $log->event === 'created' ? 'Sale created' : 'Updated: '.implode(', ', $labels),
        ];
    }

    private function applySearch(Builder $query, string $search): void
    {
        $fieldSearch = Str::lower(Str::headline(preg_replace('/^updated:\s*/i', '', $search)));
        $fields = array_keys(config('audit.fields', [])[ShipTicketSale::class] ?? []);
        $matchingFields = array_filter($fields, fn (string $field): bool => $fieldSearch !== '' && str_contains(Str::lower($this->fieldLabel($field)), $fieldSearch));
        $dates = $this->dateRange($search);

        $query->where(function (Builder $query) use ($search, $matchingFields, $dates): void {
            $query->where('user_name', 'like', '%'.$search.'%')
                ->orWhere('event', 'like', '%'.$search.'%');

            if ($dates !== null) {
                $query->orWhere(function (Builder $query) use ($dates): void {
                    $query->where('created_at', '>=', $dates['start'])->where('created_at', '<', $dates['end']);
                });
            }

            $saleId = filter_var(ltrim($search, '#'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($saleId !== false) {
                $query->orWhere('sale_id', $saleId);
            }

            if (str_contains('sale created', Str::lower($search))) {
                $query->orWhere('event', 'created');
            }

            if (str_contains('system / public booking', Str::lower($search))) {
                $query->orWhereNull('user_name');
            }

            if (str_contains('updated:', Str::lower($search))) {
                $query->orWhere('event', 'updated');
            }

            if ($matchingFields !== []) {
                $query->orWhere(function (Builder $query) use ($matchingFields): void {
                    $query->where('event', 'updated')->where(function (Builder $query) use ($matchingFields): void {
                        foreach ($matchingFields as $field) {
                            $query->orWhereJsonContainsKey('old_values->'.$field)
                                ->orWhereJsonContainsKey('new_values->'.$field);
                        }
                    });
                });
            }
        });
    }

    private function fieldLabel(string $field): string
    {
        return Str::headline(preg_replace('/_id$/', '', $field));
    }

    /** @return array{start: string, end: string}|null */
    private function dateRange(string $search): ?array
    {
        if (! preg_match('/^(?:\d{4}-\d{2}-\d{2}|\d{2} [A-Za-z]{3} \d{4})$/', $search)) {
            return null;
        }

        foreach (['Y-m-d', 'd M Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $search, new DateTimeZone(config('audit.timezone')));

            if ($date !== false && strtolower($date->format($format)) === strtolower($search)) {
                $utc = new DateTimeZone('UTC');

                return [
                    'start' => $date->setTimezone($utc)->format('Y-m-d H:i:s'),
                    'end' => $date->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s'),
                ];
            }
        }

        return null;
    }
}
