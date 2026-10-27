<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ShipTicketSale;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use LogicException;

class AuditLogService
{
    public function capture(Model $subject, Closure $action, string $event = 'updated', bool $system = false): mixed
    {
        return $subject->getConnection()->transaction(function () use ($subject, $action, $event, $system): mixed {
            $query = $subject->newQuery();

            if (in_array(SoftDeletes::class, class_uses_recursive($subject), true)) {
                $query->withTrashed();
            }

            $locked = $query->lockForUpdate()->findOrFail($subject->getKey());
            $before = $event === 'restored' ? [] : $this->snapshot($locked);
            $result = $action($locked);
            $after = $event === 'deleted' ? [] : $this->snapshot($locked->refresh());
            $this->record($locked, $event, $before, $after, system: $system);

            return $result;
        });
    }

    /** @return array<string, mixed> */
    public function snapshot(Model $subject): array
    {
        $values = [];

        foreach ($this->fields($subject) as $field => $type) {
            if (str_starts_with($type, 'collection:')) {
                $relation = config('audit.collections', [])[$subject::class][$field];
                $class = substr($type, strlen('collection:'));
                $references = array_values(config('audit.references', [])[$class] ?? []);
                $items = $subject->{$relation}()->with($references)->get()
                    ->map(fn (Model $item): array => $this->snapshot($item))->all();
                $values[$field] = $this->normalize($subject, $type, $items);

                continue;
            }

            $value = $subject->getAttribute($field);
            $reference = config('audit.references', [])[$subject::class][$field] ?? null;

            if ($reference !== null && $subject->relationLoaded($reference) && $value !== null) {
                $value = ['id' => $value, 'name' => $subject->getRelation($reference)?->name];
            }

            if ($subject instanceof ShipTicketSale && $field === 'shipment_id') {
                $value = $subject->shipment()->value('shipment_id');
            }

            $values[$field] = $this->normalize($subject, $type, $value);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(
        Model $subject,
        string $event,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
        ?string $description = null,
        bool $system = false,
    ): ?AuditLog {
        if (! in_array($event, config('audit.events', []), true)) {
            throw new InvalidArgumentException('Unsupported audit event.');
        }

        if ($subject->getKey() === null) {
            throw new InvalidArgumentException('An audit subject must have a persisted key.');
        }

        $fields = $this->fields($subject);
        $before = [];
        $after = [];

        foreach ($fields as $field => $type) {
            $hasOld = array_key_exists($field, $oldValues);
            $hasNew = array_key_exists($field, $newValues);

            if (! $hasOld && ! $hasNew) {
                continue;
            }

            $old = $this->normalize($subject, $type, $oldValues[$field] ?? null);
            $new = $this->normalize($subject, $type, $newValues[$field] ?? null);

            if (in_array($event, ['updated', 'status_changed'], true) && $old === $new) {
                continue;
            }

            if ($hasOld) {
                $before[$field] = $old;
            }

            if ($hasNew) {
                $after[$field] = $new;
            }
        }

        if (in_array($event, ['updated', 'status_changed'], true) && $before === [] && $after === []) {
            return null;
        }

        if ($subject->getConnection()->transactionLevel() === 0) {
            throw new LogicException('Save the subject and its audit log in the same database transaction.');
        }

        $actor = $system ? null : ($actor ?? auth()->user());
        $log = new AuditLog;
        $log->setConnection($subject->getConnectionName());
        $log->fill([
            'event' => $event,
            'old_values' => $before,
            'new_values' => $after,
            'user_id' => $actor?->getKey(),
            'user_name' => $actor?->name,
            'description' => $description,
            'sale_id' => $subject instanceof ShipTicketSale ? (int) $subject->getKey() : ($subject->getAttribute('sales_id') ?: null),
            'context' => ['sale_id' => $subject instanceof ShipTicketSale ? (int) $subject->getKey() : (int) $subject->getAttribute('sales_id')],
        ]);
        $log->auditable()->associate($subject);
        $log->save();

        return $log;
    }

    /** @return array<string, string> */
    private function fields(Model $subject): array
    {
        $fields = config('audit.fields', [])[$subject::class] ?? null;

        if (! is_array($fields)) {
            throw new InvalidArgumentException('No audit fields are configured for '.$subject::class.'.');
        }

        return $fields;
    }

    private function normalize(Model $subject, string $type, mixed $value): mixed
    {
        if (str_starts_with($type, 'collection:')) {
            $class = substr($type, strlen('collection:'));
            $itemSubject = new $class;
            $itemSubject->setConnection($subject->getConnectionName());
            $items = [];

            foreach ($value ?? [] as $item) {
                $normalized = [];

                foreach ($this->fields($itemSubject) as $field => $itemType) {
                    $normalized[$field] = $this->normalize($itemSubject, $itemType, $item[$field] ?? null);
                }

                $items[] = $normalized;
            }

            usort($items, fn (array $left, array $right): int => strcmp(
                json_encode($left, JSON_THROW_ON_ERROR), json_encode($right, JSON_THROW_ON_ERROR),
            ));

            return $items;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_subclass_of($type, Model::class)) {
            if (is_array($value)) {
                return ['id' => (int) $value['id'], 'name' => $value['name'] ?? null];
            }

            $related = new $type;
            $related->setConnection($subject->getConnectionName());

            return [
                'id' => (int) $value,
                'name' => $related->newQuery()->whereKey($value)->value('name'),
            ];
        }

        return match ($type) {
            'decimal' => number_format((float) $value, 2, '.', ''),
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'date' => Carbon::parse($value)->format('Y-m-d'),
            'datetime' => Carbon::parse($value)->utc()->format('Y-m-d\TH:i:s\Z'),
            default => (string) $value,
        };
    }
}
