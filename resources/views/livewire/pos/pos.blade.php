<div>
    {{-- Legacy order type modal (kept for reference; no longer rendered) --}}
    {{-- @livewire('forms.OrderTypeSelection') --}}
    <div class="flex-grow lg:flex h-auto">


        @include('pos.menu')
        @if (!$orderDetail)
            @include('pos.kot_items')
        @elseif($orderDetail->status == 'kot')
            @include('pos.order_items')
        @elseif(in_array($orderDetail->status, ['billed', 'paid', 'payment_due'], true))
            @include('pos.order_detail')
        @endif

    </div>

    <x-dialog-modal wire:model.live="showVariationModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.menu.itemVariations')
        </x-slot>

        <x-slot name="content">
            @if ($menuItem)
            @livewire('pos.itemVariations', [
                'menuItem' => $menuItem, 
                'orderTypeId' => $orderTypeId,
                'deliveryAppId' => $this->normalizedDeliveryAppId
            ], key('item-variations-' . ($menuItem->id ?? 'none') . '-' . ($orderTypeId ?? 'none') . '-' . ($this->normalizedDeliveryAppId ?? 'none')))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="$toggle('showVariationModal')" wire:loading.attr="disabled" />
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showRemovalReasonModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.order.itemAdjustmentNote')
        </x-slot>

        <x-slot name="content">
            <div>
                <x-label for="removalNote" :value="__('app.note')" />
                <x-textarea id="removalNote" class="block mt-1 w-full" wire:model.defer="removalReason" rows="3" />
                <x-input-error for="removalReason" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="cancelRemovalReason" wire:loading.attr="disabled" />
            <x-button class="ms-2" wire:click="confirmRemovalReason" wire:loading.attr="disabled">
                @lang('app.save')
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showKotNote" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.order.addNote')
        </x-slot>

        <x-slot name="content">
            <div>
                <x-label for="orderNote" :value="__('modules.order.orderNote')" />
                <x-textarea data-gramm="false"  class="block mt-1 w-full"  wire:model='orderNote' rows='2' />
                <x-input-error for="orderNote" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="$toggle('showKotNote')" wire:loading.attr="disabled">@lang('app.save')</x-button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showTableModal" maxWidth="2xl">
        <x-slot name="title">
            @lang('modules.table.availableTables')
        </x-slot>

        <x-slot name="content">
            @livewire('pos.setTable')
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="$toggle('showTableModal')" wire:loading.attr="disabled" />
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showDiscountModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.order.addDiscount')
        </x-slot>

        <x-slot name="content">
            <div class="mt-4 flex">
                <!-- Discount Value -->
                <x-input id="discountValue" class="block w-2/3 text-md" type="number" step="0.01" wire:model.defer="discountValue"
                    placeholder="{{ __('modules.order.enterDiscountValue') }}" min="0" />
                <!-- Discount Type -->
                <x-select id="discountType" class="block ml-2 w-1/3 rounded-md border-gray-300" wire:model.defer="discountType">
                    <option value="fixed">@lang('modules.order.fixed')</option>
                    <option value="percent">@lang('modules.order.percent')</option>
                </x-select>
            </div>
        <x-input-error for="discountValue" class="mt-2" />
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="$set('showDiscountModal', false)">@lang('app.cancel')</x-button-cancel>
            <x-button class="ml-3" wire:click="addDiscounts" wire:loading.attr="disabled">@lang('app.save')</x-button>
        </x-slot>
    </x-dialog-modal>


    @if ($errors->count())
        <x-dialog-modal wire:model='showErrorModal' maxWidth="xl">
            <x-slot name="title">
                @lang('app.error')
            </x-slot>

            <x-slot name="content">
                <div class="space-y-3">
                    @foreach ($errors->all() as $error)
                        <div class="text-red-700 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                                <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
                                <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                            </svg>
                            {{ $error }}
                        </div>
                    @endforeach
                </div>

            </x-slot>

            <x-slot name="footer">
                @if ($showNewKotButton)
                    <x-button class="me-2">
                        <a href="{{ route('pos.kot', ['id' => $orderDetail->id]) }}">
                            @lang('modules.order.newKot')
                        </a>
                    </x-button>
                @endif
                <x-button-cancel wire:click="closeErrorModal" wire:loading.attr="disabled" />
            </x-slot>
        </x-dialog-modal>
    @endif

    <x-dialog-modal wire:model.live="showModifiersModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.modifier.itemModifiers')
        </x-slot>

        <x-slot name="content">
            @if ($selectedModifierItem)
                @livewire('pos.itemModifiers', [
                    'menuItemId' => $selectedModifierItem,
                    'orderTypeId' => $orderTypeId,
                    'deliveryAppId' => $selectedDeliveryApp
                ], key('item-modifiers-' . ($selectedModifierItem ?? 'none') . '-' . ($orderTypeId ?? 'none') . '-' . ($selectedDeliveryApp ?? 'none')))
            @endif
        </x-slot>
    </x-dialog-modal>

    @script
    <script>
        let qtySyncTimeout = null;
        let clientOpsFlushTimer = null;
        let clientOpsInFlight = false;
        const clientOpQueue = [];
        const clientOpsTransport = String(window.POS_CLIENT_OPS_TRANSPORT || 'livewire').toLowerCase();
        const clientOpsEndpoint = String(window.POS_CLIENT_OPS_ENDPOINT || '/ajax/pos/client-ops');

        const sendClientOps = async (normalizedOps) => {
            if (clientOpsTransport === 'ajax') {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const clientState = (window.posClientState && typeof window.posClientState === 'object') ? window.posClientState : {};

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

                try {
                    const response = await fetch(clientOpsEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            state: clientState,
                            operations: normalizedOps,
                        }),
                        signal: controller.signal,
                    });

                    clearTimeout(timeoutId);

                    if (!response.ok) {
                        const errorBody = await response.text();
                        throw new Error(`POS AJAX client-op sync failed with status ${response.status}: ${errorBody}`);
                    }

                    let payload;
                    const text = await response.text();
                    try {
                        payload = JSON.parse(text);
                    } catch (parseError) {
                        throw new Error(`Failed to parse JSON response: ${text}`);
                    }

                    if (payload?.state && typeof payload.state === 'object') {
                        window.posClientState = payload.state;
                    }

                    return;
                } catch (error) {
                    clearTimeout(timeoutId);
                    if (error.name === 'AbortError') {
                        console.error('POS client-op sync timed out after 10s');
                    } else {
                        console.error('POS client-op sync error:', error.message);
                    }
                    throw error;
                }
            }

            await $wire.call('applyClientOps', normalizedOps);
        };

        const flushClientOps = async () => {
            if (clientOpsInFlight || clientOpQueue.length === 0) {
                return;
            }

            clientOpsInFlight = true;

            const queuedOps = clientOpQueue.splice(0, 40);
            const normalizedOps = [];
            const lineStates = new Map();

            const upsertLineState = (key, nextState) => {
                if (!key) {
                    return;
                }

                if (nextState === null) {
                    lineStates.delete(key);
                    return;
                }

                lineStates.set(key, nextState);
            };

            for (const op of queuedOps) {
                if (!op || typeof op !== 'object') {
                    continue;
                }

                const opType = String(op.type || '');

                if (opType === 'qty_delta') {
                    const key = String(op.key || '');
                    const delta = Number(op.delta || 0);

                    if (!key || Number.isNaN(delta) || delta === 0) {
                        continue;
                    }

                    const currentState = lineStates.get(key);

                    if (currentState?.type === 'remove_item') {
                        continue;
                    }

                    if (currentState?.type === 'qty_set') {
                        const nextQty = Number(currentState.qty || 0) + delta;
                        if (nextQty <= 0) {
                            upsertLineState(key, { type: 'remove_item', key });
                        } else {
                            upsertLineState(key, { type: 'qty_set', key, qty: nextQty });
                        }
                        continue;
                    }

                    const nextDelta = Number(currentState?.delta || 0) + delta;
                    if (nextDelta === 0) {
                        upsertLineState(key, null);
                    } else {
                        upsertLineState(key, { type: 'qty_delta', key, delta: nextDelta });
                    }
                    continue;
                }

                if (opType === 'qty_set') {
                    const key = String(op.key || '');
                    const qty = Number(op.qty || 0);

                    if (!key || Number.isNaN(qty)) {
                        continue;
                    }

                    if (qty <= 0) {
                        upsertLineState(key, { type: 'remove_item', key });
                    } else {
                        upsertLineState(key, { type: 'qty_set', key, qty });
                    }
                    continue;
                }

                if (opType === 'remove_item') {
                    const key = String(op.key || '');
                    if (!key) {
                        continue;
                    }

                    upsertLineState(key, { type: 'remove_item', key });
                    continue;
                }

                normalizedOps.push(op);
            }

            lineStates.forEach((state) => {
                normalizedOps.push(state);
            });

            try {
                if (normalizedOps.length > 0) {
                    await sendClientOps(normalizedOps);
                }
            } catch (error) {
                for (let i = queuedOps.length - 1; i >= 0; i--) {
                    clientOpQueue.unshift(queuedOps[i]);
                }
                console.error('POS client-op sync failed', error);
            } finally {
                clientOpsInFlight = false;

                if (clientOpQueue.length > 0) {
                    clientOpsFlushTimer = setTimeout(flushClientOps, 100);
                }
            }
        };

        const queueClientOp = (operation) => {
            clientOpQueue.push(operation);

            if (clientOpsFlushTimer) {
                clearTimeout(clientOpsFlushTimer);
            }

            clientOpsFlushTimer = setTimeout(flushClientOps, 80);
        };

        window.posClient = {
            queueAddItem(payload) {
                const id = Number(payload?.id || 0);
                const variationCount = Number(payload?.variationCount || 0);
                const modifierCount = Number(payload?.modifierCount || 0);

                if (variationCount > 0 || modifierCount > 0) {
                    if (clientOpsFlushTimer) {
                        clearTimeout(clientOpsFlushTimer);
                        clientOpsFlushTimer = null;
                    }

                    flushClientOps().finally(() => {
                        $wire.call('addCartItems', id, variationCount, modifierCount)
                            .catch((error) => {
                                console.error('POS immediate add-item failed', error);
                            });
                    });
                    return;
                }

                queueClientOp({
                    type: 'add_item',
                    id,
                    variationCount,
                    modifierCount,
                });
            },

            queueDeleteItem(key, sourceEl = null) {
                const safeKey = String(key || '');

                if (!safeKey) {
                    return;
                }

                const row = sourceEl?.closest('tr');
                if (row) {
                    row.dataset.pendingDelete = '1';
                    row.style.opacity = '0.6';
                    row.style.pointerEvents = 'none';
                }

                queueClientOp({
                    type: 'remove_item',
                    key: safeKey,
                });
            },

            queueQtySet(key, qty, sourceEl = null) {
                const safeKey = String(key || '');
                const rawQty = Number(qty || 0);
                const safeQty = Math.max(1, Math.floor(rawQty));

                if (!safeKey || Number.isNaN(safeQty)) {
                    return;
                }

                const wrapper = sourceEl?.closest('div.relative.flex.items-center');
                const qtyInput = wrapper?.querySelector('input[data-pos-qty-key]');

                if (qtyInput) {
                    qtyInput.value = String(safeQty);
                }

                queueClientOp({
                    type: 'qty_set',
                    key: safeKey,
                    qty: safeQty,
                });
            },

            queueAddCombo(comboId) {
                queueClientOp({
                    type: 'add_combo',
                    comboId: Number(comboId || 0),
                });
            },

            queueQtyDelta(key, delta, sourceEl = null) {
                const safeKey = String(key || '');
                const safeDelta = Number(delta || 0);

                if (!safeKey || Number.isNaN(safeDelta) || safeDelta === 0) {
                    return;
                }

                const wrapper = sourceEl?.closest('div.relative.flex.items-center');
                const qtyInput = wrapper?.querySelector('input[data-pos-qty-key]');

                if (qtyInput) {
                    const currentVal = Number(qtyInput.value || 0);
                    const nextVal = Math.max(0, currentVal + safeDelta);
                    qtyInput.value = String(nextVal);
                }

                queueClientOp({
                    type: 'qty_delta',
                    key: safeKey,
                    delta: safeDelta,
                });
            },
        };

        $wire.on('play_beep', () => {
            new Audio("{{ asset('sound/sound_beep-29.mp3')}}").play();
        });

        $wire.on('print_location', (url) => {
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.target = '_blank';
            anchor.click();
        });

        $wire.on('scheduleQtySync', (payload) => {
            const delay = payload?.delay ?? (Array.isArray(payload) ? payload[0]?.delay : null) ?? 1000;

            if (qtySyncTimeout) {
                clearTimeout(qtySyncTimeout);
            }

            qtySyncTimeout = setTimeout(() => {
                $wire.call('syncPendingQtys');
            }, delay);
        });

    </script>

    @endscript

</div>
