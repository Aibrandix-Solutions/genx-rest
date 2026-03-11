<?php

namespace App\Support;

use App\Models\KotItem;
use App\Models\KotItemAdjustment;
use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class KotAdjustmentLogger
{
    public static function log(
        KotItem $kotItem,
        string $action,
        string $note,
        int $quantityBefore,
        int $quantityAfter = 0
    ): void {
        $kotItem->loadMissing([
            'menuItem',
            'menuItemVariation.menuItem',
            'kot.order.table',
            'kot.table',
        ]);

        $order = $kotItem->kot?->order;
        $tableCode = $order?->table?->table_code ?? $kotItem->kot?->table?->table_code;
        $menuItemName = $kotItem->menuItem?->item_name
            ?? $kotItem->menuItemVariation?->menuItem?->item_name
            ?? __('messages.menuItemDeleted');
        $variationName = $kotItem->menuItemVariation?->variation;

        $columns = self::getTableColumns();

        $payload = [
            'order_id' => $kotItem->kot->order_id ?? null,
            'restaurant_id' => $order?->restaurant_id ?? restaurant()?->id,
            'branch_id' => $order?->branch_id ?? branch()?->id,
            'kot_id' => $kotItem->kot_id,
            'kot_item_id' => $kotItem->id,
            'menu_item_id' => $kotItem->menu_item_id,
            'menu_item_variation_id' => $kotItem->menu_item_variation_id,
            'menu_item_name' => $menuItemName,
            'menu_item_variation_name' => $variationName,
            'order_number' => $order?->order_number,
            'formatted_order_number' => $order?->formatted_order_number,
            'table_code' => $tableCode,
            'performed_by' => user()->id ?? null,
            'performed_by_name' => user()?->name,
            'action' => $action,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'note' => $note,
        ];

        KotItemAdjustment::create(self::filterPayload($payload, $columns));
    }

    /**
     * Log a deletion that originated from an OrderItem directly (no KotItem exists,
     * e.g. items removed after billing from billed/paid orders).
     */
    public static function logOrderItem(
        OrderItem $orderItem,
        string $action,
        string $note,
        int $quantityBefore,
        int $quantityAfter = 0
    ): void {
        $orderItem->loadMissing(['menuItem', 'menuItemVariation', 'order.table']);

        $order = $orderItem->order;
        $tableCode = $order?->table?->table_code;
        $menuItemName = $orderItem->menuItem?->item_name
            ?? $orderItem->menuItemVariation?->menuItem?->item_name
            ?? __('messages.menuItemDeleted');
        $variationName = $orderItem->menuItemVariation?->variation;

        $columns = self::getTableColumns();

        $payload = [
            'order_id'                  => $orderItem->order_id,
            'restaurant_id'             => $order?->restaurant_id ?? restaurant()?->id,
            'branch_id'                 => $order?->branch_id ?? branch()?->id,
            'kot_id'                    => null,
            'kot_item_id'               => null,
            'menu_item_id'              => $orderItem->menu_item_id,
            'menu_item_variation_id'    => $orderItem->menu_item_variation_id,
            'menu_item_name'            => $menuItemName,
            'menu_item_variation_name'  => $variationName,
            'order_number'              => $order?->order_number,
            'formatted_order_number'    => $order?->formatted_order_number,
            'table_code'                => $tableCode,
            'performed_by'              => user()->id ?? null,
            'performed_by_name'         => user()?->name,
            'action'                    => $action,
            'quantity_before'           => $quantityBefore,
            'quantity_after'            => $quantityAfter,
            'note'                      => $note,
        ];

        KotItemAdjustment::create(self::filterPayload($payload, $columns));
    }

    protected static function getTableColumns(): array
    {
        static $columns = null;

        if (is_null($columns)) {
            $columns = Schema::hasTable('kot_item_adjustments')
                ? Schema::getColumnListing('kot_item_adjustments')
                : [];
        }

        return $columns;
    }

    protected static function filterPayload(array $payload, array $columns): array
    {
        if (empty($columns)) {
            return Arr::only($payload, [
                'order_id',
                'kot_id',
                'kot_item_id',
                'performed_by',
                'action',
                'quantity_before',
                'quantity_after',
                'note',
            ]);
        }

        return Arr::only($payload, $columns);
    }
}
