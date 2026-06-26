<?php

namespace App\Support;

use Carbon\Carbon;

class ActivityLogPropertyFormatter
{
    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function format(?array $properties, ?string $event = null): array
    {
        if (empty($properties)) {
            return [];
        }

        $rows = [];

        foreach (self::sortKeys(array_keys($properties), $event) as $key) {
            if (!array_key_exists($key, $properties)) {
                continue;
            }

            if (self::shouldSkipKey($key, $properties)) {
                continue;
            }

            $value = self::formatValue($key, $properties[$key]);

            if ($value === null || $value === '') {
                continue;
            }

            $rows[] = [
                'label' => self::labelForKey($key),
                'value' => $value,
            ];
        }

        return $rows;
    }

    public static function formatAsText(?array $properties, ?string $event = null): string
    {
        return collect(self::format($properties, $event))
            ->map(fn (array $row) => $row['label'] . ': ' . $row['value'])
            ->implode('; ');
    }

    protected static function labelForKey(string $key): string
    {
        $translationKey = 'app.activityLog.propertyLabels.' . $key;

        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }

        return ucwords(str_replace('_', ' ', $key));
    }

    protected static function shouldSkipKey(string $key, array $properties): bool
    {
        $skipWhenNamePresent = [
            'supplier_id' => 'supplier_name',
            'staff_id' => 'staff_name',
            'user_id' => 'user_name',
            'role_id' => 'role_name',
            'menu_item_id' => 'menu_item_name',
            'purchase_order_id' => 'po_number',
            'expense_id' => 'expense_title',
        ];

        if (isset($skipWhenNamePresent[$key]) && !empty($properties[$skipWhenNamePresent[$key]])) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    protected static function sortKeys(array $keys, ?string $event): array
    {
        $priority = [
            'formatted_order_number',
            'order_number',
            'order_id',
            'po_number',
            'purchase_order_id',
            'supplier_name',
            'supplier_id',
            'amount',
            'old_amount',
            'payment_method',
            'old_payment_method',
            'paid_on',
            'old_paid_on',
            'payment_account_id',
            'transaction_id',
            'note',
            'status',
            'old_status',
            'action',
            'menu_item_name',
            'menu_item_variation_name',
            'quantity_before',
            'quantity_after',
            'table_code',
            'expense_title',
            'category',
            'role_name',
            'permission',
            'email',
            'guest_name',
            'room_number',
            'folio_id',
            'charge_type',
            'task_type',
            'opening_balance',
            'closing_balance',
            'reason',
            'item_name',
            'quantity',
            'transaction_type',
        ];

        $priorityMap = array_flip($priority);

        usort($keys, function (string $a, string $b) use ($priorityMap) {
            $rankA = $priorityMap[$a] ?? 999;
            $rankB = $priorityMap[$b] ?? 999;

            if ($rankA === $rankB) {
                return strcmp($a, $b);
            }

            return $rankA <=> $rankB;
        });

        return $keys;
    }

    protected static function formatValue(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? __('app.yes') : __('app.no');
        }

        if (is_array($value)) {
            $parts = [];

            foreach ($value as $item) {
                if (is_scalar($item) || $item === null) {
                    $parts[] = (string) $item;
                    continue;
                }

                if (is_array($item)) {
                    $parts[] = collect($item)
                        ->filter(fn ($v) => $v !== null && $v !== '')
                        ->map(fn ($v, $k) => is_int($k) ? (string) $v : self::labelForKey((string) $k) . ': ' . $v)
                        ->implode(', ');
                }
            }

            return $parts !== [] ? implode('; ', $parts) : null;
        }

        if (self::isMoneyKey($key) && is_numeric($value)) {
            return currency_format($value);
        }

        if (self::isDateKey($key)) {
            return self::formatDateValue($value);
        }

        if ($key === 'payment_method' || str_ends_with($key, '_method')) {
            return ucwords(str_replace('_', ' ', (string) $value));
        }

        if ($key === 'action' || $key === 'status' || str_ends_with($key, '_status')) {
            return ucwords(str_replace('_', ' ', (string) $value));
        }

        return (string) $value;
    }

    protected static function isMoneyKey(string $key): bool
    {
        if (in_array($key, [
            'amount',
            'old_amount',
            'total',
            'total_amount',
            'discount',
            'discount_value',
            'delivery_fee',
            'opening_balance',
            'closing_balance',
            'unit_purchase_price',
            'balance',
            'fee',
            'price',
            'charge_amount',
        ], true)) {
            return true;
        }

        return str_ends_with($key, '_amount') || str_ends_with($key, '_fee') || str_ends_with($key, '_price');
    }

    protected static function isDateKey(string $key): bool
    {
        return in_array($key, ['paid_on', 'old_paid_on', 'check_in', 'check_out', 'reservation_date'], true)
            || str_ends_with($key, '_at')
            || str_ends_with($key, '_on')
            || str_ends_with($key, '_date');
    }

    protected static function formatDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(timezone())->format('d M Y, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
