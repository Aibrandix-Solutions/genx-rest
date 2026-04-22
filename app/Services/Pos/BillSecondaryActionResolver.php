<?php

namespace App\Services\Pos;

class BillSecondaryActionResolver
{
    /**
     * Resolve bill follow-up behavior shared by Livewire and Vue API flows.
     *
     * @return array{secondary_action:?string,open_payment:bool,print_receipt:bool,show_order_detail:bool}
     */
    public function resolve(string $action, ?string $secondaryAction): array
    {
        $normalizedSecondaryAction = in_array($secondaryAction, ['payment', 'print'], true)
            ? $secondaryAction
            : null;

        if ($action !== 'bill') {
            return [
                'secondary_action' => null,
                'open_payment' => false,
                'print_receipt' => false,
                'show_order_detail' => false,
            ];
        }

        return [
            'secondary_action' => $normalizedSecondaryAction,
            'open_payment' => $normalizedSecondaryAction === 'payment',
            'print_receipt' => $normalizedSecondaryAction === 'print',
            'show_order_detail' => $normalizedSecondaryAction === null,
        ];
    }
}