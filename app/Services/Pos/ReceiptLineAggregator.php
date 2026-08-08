<?php

namespace App\Services\Pos;

use App\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Customer-facing receipts combine identical menu lines even when they
 * were sent on separate KOTs. Kitchen tickets are unchanged.
 */
class ReceiptLineAggregator
{
    public static function mergeIdenticalItems(iterable $items): Collection
    {
        $out = collect();
        $indexByKey = [];

        foreach ($items as $item) {
            if (self::isComboLine($item)) {
                $out->push($item);
                continue;
            }

            $key = self::signature($item);

            if (! isset($indexByKey[$key])) {
                $indexByKey[$key] = $out->count();
                $out->push(self::cloneForDisplay($item));
                continue;
            }

            $existing = $out->get($indexByKey[$key]);
            $existing->quantity = (int) ($existing->quantity ?? 0) + (int) ($item->quantity ?? 0);
            $existing->amount = round((float) ($existing->amount ?? 0) + (float) ($item->amount ?? 0), 2);
            $existing->item_discount_amount = round(
                (float) ($existing->item_discount_amount ?? 0) + (float) ($item->item_discount_amount ?? 0),
                2
            );
            $existing->tax_amount = round(
                (float) ($existing->tax_amount ?? 0) + (float) ($item->tax_amount ?? 0),
                2
            );
        }

        return $out->values();
    }

    private static function isComboLine(object $item): bool
    {
        return (bool) ($item->is_combo_item ?? false) && ! empty($item->combo_pack_id);
    }

    private static function cloneForDisplay(object $item): object
    {
        if ($item instanceof OrderItem) {
            $clone = clone $item;
            if ($item->relationLoaded('menuItem')) {
                $clone->setRelation('menuItem', $item->menuItem);
            }
            if ($item->relationLoaded('menuItemVariation')) {
                $clone->setRelation('menuItemVariation', $item->menuItemVariation);
            }
            if ($item->relationLoaded('modifierOptions')) {
                $clone->setRelation('modifierOptions', $item->modifierOptions);
            }
            if ($item->relationLoaded('comboPack')) {
                $clone->setRelation('comboPack', $item->comboPack);
            }

            return $clone;
        }

        return clone $item;
    }

    private static function signature(object $item): string
    {
        $modifiers = collect($item->modifierOptions ?? []);

        $modSig = $modifiers
            ->map(fn ($modifier) => (int) $modifier->id.':'.(int) ($modifier->pivot->quantity ?? 1))
            ->sort()
            ->values()
            ->implode(',');

        $note = trim((string) ($item->note ?? ''));
        $note = trim((string) preg_replace('/\[COMBO_INSTANCE:[^\]]+\]/', '', $note));

        return implode('|', [
            (int) ($item->menu_item_id ?? 0),
            (int) ($item->menu_item_variation_id ?? 0),
            $modSig,
            $note,
            number_format((float) ($item->price ?? 0), 2, '.', ''),
            (string) ($item->discount_type ?? ''),
            number_format((float) ($item->discount_value ?? 0), 2, '.', ''),
        ]);
    }
}
