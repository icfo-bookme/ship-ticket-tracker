@props(['value', 'field' => ''])

@if ($value === null || $value === '')
    <span class="text-gray-400">—</span>
@elseif (is_bool($value))
    {{ $value ? 'Yes' : 'No' }}
@elseif (is_array($value) && array_key_exists('id', $value) && array_key_exists('name', $value))
    {{ $value['name'] ?? 'Record #'.$value['id'] }}
@elseif (is_array($value))
    @if ($value === [])
        <span class="text-gray-400">None</span>
    @else
        <div class="flex flex-col gap-2">
            @foreach ($value as $key => $item)
                <div class="rounded border border-gray-200 p-2 dark:border-gray-600">
                    @if (is_string($key))
                        <span class="font-medium">{{ \Illuminate\Support\Str::headline($key) }}:</span>
                    @endif
                    <x-audit-value :value="$item" :field="is_string($key) ? $key : ''" />
                </div>
            @endforeach
        </div>
    @endif
@elseif (is_numeric($value) && preg_match('/(amount|fee|payable|price)$/', $field))
    ৳ {{ number_format((float) $value, 2) }}
@elseif ($field === 'status' || $field === 'settlement_status')
    {{ \Illuminate\Support\Str::headline((string) $value) }}
@else
    {{ $value }}
@endif
