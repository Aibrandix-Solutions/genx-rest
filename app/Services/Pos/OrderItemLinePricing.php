<?php

namespace App\Services\Pos;

use App\Models\MenuItem;

class OrderItemLinePricing
{
    /**
     * @return array{price: float, amount: float, discount_type: ?string, discount_value: ?float, item_discount_amount: ?float}
     */
    public static function compute(float $unitPrice, int $qty, ?string $discountType = null, ?float $discountValue = null): array
    {
        $qty = max(1, $qty);
        $unitPrice = round(max(0, $unitPrice), 2);
        $lineSubtotal = round($unitPrice * $qty, 2);

        $itemDiscountAmount = 0.0;
        $normalizedType = null;
        $normalizedValue = null;

        if ($discountType && $discountValue !== null && (float) $discountValue > 0) {
            $value = (float) $discountValue;
            if ($discountType === 'percent') {
                $value = min($value, 100);
                $itemDiscountAmount = round($lineSubtotal * $value / 100, 2);
                $normalizedType = 'percent';
                $normalizedValue = $value;
            } elseif ($discountType === 'fixed') {
                $itemDiscountAmount = min(round($value, 2), $lineSubtotal);
                $normalizedType = 'fixed';
                $normalizedValue = round($value, 2);
            }
        }

        if ($itemDiscountAmount <= 0) {
            return [
                'price' => $unitPrice,
                'amount' => $lineSubtotal,
                'discount_type' => null,
                'discount_value' => null,
                'item_discount_amount' => null,
            ];
        }

        return [
            'price' => $unitPrice,
            'amount' => max(0, round($lineSubtotal - $itemDiscountAmount, 2)),
            'discount_type' => $normalizedType,
            'discount_value' => $normalizedValue,
            'item_discount_amount' => $itemDiscountAmount,
        ];
    }

    /**
     * @return array{tax_amount: ?float, tax_percentage: ?float, tax_breakup: ?string}
     */
    public static function itemTaxForLine(MenuItem $menuItem, float $lineAmount, int $qty): array
    {
        if ($qty <= 0 || $lineAmount <= 0 || $menuItem->taxes->isEmpty()) {
            return [
                'tax_amount' => null,
                'tax_percentage' => null,
                'tax_breakup' => null,
            ];
        }

        $effectiveUnit = round($lineAmount / $qty, 2);
        $isInclusive = (bool) (restaurant()->tax_inclusive ?? false);
        $taxResult = MenuItem::calculateItemTaxes($effectiveUnit, $menuItem->taxes, $isInclusive);

        return [
            'tax_amount' => round((float) ($taxResult['tax_amount'] ?? 0) * $qty, 2),
            'tax_percentage' => $taxResult['tax_percentage'] ?? null,
            'tax_breakup' => json_encode($taxResult['tax_breakdown'] ?? []),
        ];
    }

    public static function linePayloadFromModel($item): array
    {
        $discountAmount = (float) ($item->item_discount_amount ?? 0);

        return [
            'discount_type' => $discountAmount > 0 ? $item->discount_type : null,
            'discount_value' => $discountAmount > 0 ? (float) $item->discount_value : null,
            'item_discount_amount' => $discountAmount > 0 ? $discountAmount : null,
        ];
    }
}
