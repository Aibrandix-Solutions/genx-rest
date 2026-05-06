<?php

namespace App\Services;

class PosBatchSyncService
{
    public function apply(array $state, array $operations): array
    {
        if ($this->looksLikeTypedOperations($operations)) {
            return $this->applyTypedOperations($state, $operations);
        }

        $normalizedOperations = $this->normalizeOperations($operations);
        $removedIds = [];

        foreach ($normalizedOperations as $id => $operation) {
            $action = $operation['action'] ?? 'update';
            $qty = (int) ($operation['qty'] ?? 0);

            if ($action === 'remove' || $action === 'delete' || $qty <= 0) {
                $this->removeLineState($state, (string) $id);
                $removedIds[] = (string) $id;
                continue;
            }

            $lineId = (string) $id;
            $previousQty = (int) ($state['orderItemQty'][$lineId] ?? 0);
            $state['orderItemQty'][$lineId] = $qty;
            $this->syncLineComputedState($state, $lineId, $previousQty, $qty);
        }

        return [
            'state' => $state,
            'applied' => count($normalizedOperations),
            'removed_ids' => $removedIds,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    private function looksLikeTypedOperations(array $operations): bool
    {
        foreach ($operations as $operation) {
            if (is_array($operation) && array_key_exists('type', $operation)) {
                return true;
            }
        }

        return false;
    }

    private function applyTypedOperations(array $state, array $operations): array
    {
        $applied = 0;
        $removedIds = [];

        foreach ($operations as $operation) {
            if (!is_array($operation)) {
                continue;
            }

            $type = (string) ($operation['type'] ?? '');
            $lineId = (string) ($operation['key'] ?? '');

            if ($lineId === '') {
                continue;
            }

            if ($type === 'remove_item') {
                $this->removeLineState($state, $lineId);
                $removedIds[] = $lineId;
                $applied++;
                continue;
            }

            if ($type === 'qty_set') {
                $nextQty = (int) ($operation['qty'] ?? 0);
                if ($nextQty <= 0) {
                    $this->removeLineState($state, $lineId);
                    $removedIds[] = $lineId;
                } else {
                    $previousQty = (int) ($state['orderItemQty'][$lineId] ?? 0);
                    $state['orderItemQty'][$lineId] = $nextQty;
                    $this->syncLineComputedState($state, $lineId, $previousQty, $nextQty);
                }
                $applied++;
                continue;
            }

            if ($type === 'qty_delta') {
                $delta = (int) ($operation['delta'] ?? 0);
                if ($delta === 0) {
                    continue;
                }

                $previousQty = (int) ($state['orderItemQty'][$lineId] ?? 0);
                $nextQty = $previousQty + $delta;

                if ($nextQty <= 0) {
                    $this->removeLineState($state, $lineId);
                    $removedIds[] = $lineId;
                } else {
                    $state['orderItemQty'][$lineId] = $nextQty;
                    $this->syncLineComputedState($state, $lineId, $previousQty, $nextQty);
                }

                $applied++;
            }
        }

        return [
            'state' => $state,
            'applied' => $applied,
            'removed_ids' => array_values(array_unique($removedIds)),
            'synced_at' => now()->toIso8601String(),
        ];
    }

    private function normalizeOperations(array $operations): array
    {
        $normalized = [];

        foreach ($operations as $id => $operation) {
            if (is_array($operation)) {
                $action = strtolower((string) ($operation['action'] ?? 'update'));
                if ($action === 'sub') {
                    $action = 'update';
                }
                if ($action === 'delete') {
                    $action = 'remove';
                }
                $normalized[(string) $id] = [
                    'action' => $action,
                    'qty' => $operation['qty'] ?? null,
                ];
            }
        }

        return $normalized;
    }

    private function readPrice($value): float
    {
        if (is_array($value)) {
            return (float) ($value['price'] ?? 0);
        }

        if (is_object($value)) {
            return (float) ($value->price ?? 0);
        }

        return 0.0;
    }

    private function syncLineComputedState(array &$state, string $lineId, int $previousQty, int $newQty): void
    {
        $variation = $state['orderItemVariation'][$lineId] ?? null;
        $item = $state['orderItemList'][$lineId] ?? null;
        $variationPrice = $variation ? $this->readPrice($variation) : 0.0;
        $itemPrice = $item ? $this->readPrice($item) : 0.0;
        $basePrice = $variationPrice > 0 ? $variationPrice : $itemPrice;

        $modifierPrice = (float) ($state['orderItemModifiersPrice'][$lineId] ?? 0);
        $lineAmount = $newQty * ($basePrice + $modifierPrice);        $state['orderItemAmount'][$lineId] = $lineAmount;

        if (isset($state['orderItemTaxDetails'][$lineId]) && is_array($state['orderItemTaxDetails'][$lineId])) {
            $state['orderItemTaxDetails'][$lineId] = $this->recalculateTaxDetails(
                $state['orderItemTaxDetails'][$lineId],
                $previousQty,
                $newQty
            );
        }
    }

    private function recalculateTaxDetails(array $taxDetails, int $previousQty, int $newQty): array
    {
        $previousQty = max(1, $previousQty);
        $perUnitTaxAmount = (float) ($taxDetails['tax_amount'] ?? 0) / $previousQty;

        $taxDetails['tax_amount'] = round($perUnitTaxAmount * $newQty, 2);

        return $taxDetails;
    }

    private function removeLineState(array &$state, string $id): void
    {
        $comboInstanceKey = $state['orderItemComboPack'][$id] ?? null;

        $keysToUnset = [
            'orderItemQty',
            'orderItemAmount',
            'orderItemList',
            'orderItemVariation',
            'itemModifiersSelected',
            'orderItemModifiersPrice',
            'orderItemTaxDetails',
            'itemNotes',
            'orderItemComboPack',
            'orderItemComboDiscount',
            'orderItemUnitPrice',
            'orderItemDisplayPrice',
            'orderItemOriginalPrice',
            'orderItemPersistedTaxOverride',
        ];

        foreach ($keysToUnset as $key) {
            if (isset($state[$key]) && is_array($state[$key])) {
                unset($state[$key][$id]);
            }
        }

        if (isset($state['orderItemComboName']) && is_array($state['orderItemComboName'])) {
            if ($comboInstanceKey) {
                unset($state['orderItemComboName'][$comboInstanceKey]);
            }
        }
    }
}
