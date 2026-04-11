<?php

namespace App\Services;

class PosBatchSyncService
{
    public function apply(array $state, array $operations): array
    {
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

            $variation = $state['orderItemVariation'][$lineId] ?? null;
            $item = $state['orderItemList'][$lineId] ?? null;
            $basePrice = $this->readPrice($variation) ?: $this->readPrice($item);

            $modifierPrice = (float) ($state['orderItemModifiersPrice'][$lineId] ?? 0);
            $lineAmount = $qty * ($basePrice + $modifierPrice);
            $state['orderItemAmount'][$lineId] = $lineAmount;

            if (isset($state['orderItemTaxDetails'][$lineId]) && is_array($state['orderItemTaxDetails'][$lineId])) {
                $state['orderItemTaxDetails'][$lineId] = $this->recalculateTaxDetails(
                    $state['orderItemTaxDetails'][$lineId],
                    $previousQty,
                    $qty
                );
            }
        }

        return [
            'state' => $state,
            'applied' => count($normalizedOperations),
            'removed_ids' => $removedIds,
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
