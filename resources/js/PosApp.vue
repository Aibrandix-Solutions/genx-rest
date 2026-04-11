<template>
    <div class="p-4 lg:p-6">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">POS Vue Shell</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">Client-first cart, local totals, server save next.</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="isOnline" class="rounded bg-green-100 px-2 py-1 text-green-700">online</span>
                <span v-else class="rounded bg-red-100 px-2 py-1 text-red-700">offline</span>
                <span v-if="pendingOrders.length > 0" class="rounded bg-amber-100 px-2 py-1 text-amber-700">
                    pending {{ pendingOrders.length }}
                </span>
                <span v-if="bootstrap.cached" class="rounded bg-green-100 px-2 py-1 text-green-700">cache hit</span>
                <span v-else class="rounded bg-yellow-100 px-2 py-1 text-yellow-700">cache miss</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <section class="lg:col-span-7 rounded border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-center">
                    <input
                        v-model.trim="search"
                        type="text"
                        placeholder="Search menu items"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
                    />
                    <select v-model="menuId" class="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        <option :value="null">All menus</option>
                        <option v-for="m in bootstrap.menus" :key="m.id" :value="m.id">{{ m.menu_name }}</option>
                    </select>
                    <select v-model="categoryId" class="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        <option :value="null">All categories</option>
                        <option v-for="c in bootstrap.categories" :key="c.id" :value="c.id">{{ c.category_name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
                    <button
                        v-for="item in filteredItems"
                        :key="item.id"
                        type="button"
                        class="rounded border border-gray-200 bg-white p-2 text-left transition hover:border-skin-base hover:shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        :disabled="!item.in_stock"
                        @click="openItemConfig(item)"
                    >
                        <img :src="item.item_photo_url" :alt="item.item_name" class="mb-2 h-24 w-full rounded object-cover" />
                        <div class="line-clamp-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.item_name }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ formatMoney(item.price) }}
                            <span v-if="item.variations_count > 0" class="ml-1 rounded bg-gray-100 px-1 py-0.5 text-[10px]">var</span>
                            <span v-if="item.modifier_groups_count > 0" class="ml-1 rounded bg-gray-100 px-1 py-0.5 text-[10px]">mods</span>
                            <span v-if="!item.in_stock" class="ml-1 text-red-500">Out</span>
                        </div>
                    </button>
                </div>

                <div v-if="bootstrap.combo_packs?.length" class="mt-4">
                    <div class="mb-2 text-xs font-semibold uppercase text-gray-500">Combo Packs</div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <button
                            v-for="combo in bootstrap.combo_packs"
                            :key="`combo-${combo.id}`"
                            type="button"
                            class="rounded border border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50 p-2 text-left transition hover:border-skin-base hover:shadow-sm dark:border-blue-800 dark:from-blue-900/30 dark:to-cyan-900/20"
                            @click="addComboPack(combo)"
                        >
                            <div class="line-clamp-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ combo.name }}</div>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                <span class="line-through">{{ formatMoney(combo.regular_price) }}</span>
                                <span class="ml-1 font-semibold text-green-600 dark:text-green-400">{{ formatMoney(combo.discounted_price) }}</span>
                            </div>
                            <div class="mt-1 text-[11px] text-blue-600 dark:text-blue-300">{{ combo.discount_percent }}% OFF</div>
                        </button>
                    </div>
                </div>
            </section>

            <section class="lg:col-span-5 rounded border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Order Panel</h2>
                    <span class="text-xs text-gray-500">{{ cart.length }} items</span>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-2">
                    <select v-model="orderTypeId" class="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        <option :value="null">Order type</option>
                        <option v-for="ot in bootstrap.order_types" :key="ot.id" :value="ot.id">{{ ot.order_type_name }}</option>
                    </select>
                    <select v-model="waiterId" class="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        <option :value="null">Waiter</option>
                        <option v-for="w in bootstrap.waiters" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>

                <div class="mb-3 rounded border border-gray-200 p-2 text-xs dark:border-gray-700">
                    <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Order Action</div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-gray-300 px-2 py-1.5 dark:border-gray-600">
                            <input v-model="orderAction" type="radio" value="kot" />
                            <span>KOT</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-gray-300 px-2 py-1.5 dark:border-gray-600">
                            <input v-model="orderAction" type="radio" value="bill" />
                            <span>Bill</span>
                        </label>
                    </div>
                    <label v-if="orderAction === 'bill'" class="mt-2 inline-flex cursor-pointer items-center gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                        <input v-model="openPaymentAfterBill" type="checkbox" />
                        <span>Open payment modal after billing</span>
                    </label>
                </div>

                <div class="max-h-[50vh] space-y-2 overflow-auto pr-1">
                    <div v-if="cart.length === 0" class="rounded border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-600">
                        No item added
                    </div>

                    <div v-for="line in cart" :key="line.key" class="rounded border border-gray-200 p-2 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ line.item_name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ formatMoney(line.unit_price) }} x {{ line.qty }}
                                    <span v-if="line.variation_name">| {{ line.variation_name }}</span>
                                    <span v-if="line.is_combo_item" class="ml-1 rounded bg-blue-100 px-1 py-0.5 text-[10px] text-blue-700">combo</span>
                                </div>
                                <div v-if="line.modifier_labels?.length" class="mt-1 text-[11px] text-gray-500">
                                    {{ line.modifier_labels.join(', ') }}
                                </div>
                            </div>
                            <button type="button" class="text-xs text-red-600" @click="removeLine(line.key)">Remove</button>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="inline-flex items-center rounded border border-gray-300 dark:border-gray-600">
                                <button type="button" class="px-2 py-1" :disabled="line.is_combo_item" @click="deltaQty(line.key, -1)">-</button>
                                <span class="px-2 py-1 text-sm">{{ line.qty }}</span>
                                <button type="button" class="px-2 py-1" :disabled="line.is_combo_item" @click="deltaQty(line.key, 1)">+</button>
                            </div>
                            <div class="text-sm font-semibold">{{ formatMoney(line.amount) }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-1 border-t border-gray-200 pt-3 text-sm dark:border-gray-700">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ formatMoney(subtotal) }}</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ formatMoney(totalTax) }}</span></div>
                    <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100"><span>Total</span><span>{{ formatMoney(total) }}</span></div>
                </div>

                <button
                    type="button"
                    class="mt-4 w-full rounded bg-skin-base px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                    :disabled="cart.length === 0 || saving"
                    @click="saveOrder"
                >
                    {{ saving ? 'Saving...' : (orderAction === 'bill' ? 'Save Bill' : 'Save KOT') }}
                </button>

                <button
                    type="button"
                    class="mt-2 w-full rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-60 dark:border-gray-600 dark:text-gray-200"
                    :disabled="pendingOrders.length === 0 || !isOnline || syncingPending"
                    @click="syncPendingOrders"
                >
                    {{ syncingPending ? 'Syncing...' : 'Sync Pending Orders' }}
                </button>
            </section>
        </div>

        <div v-if="showItemConfigModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeItemConfig">
            <div class="w-full max-w-lg rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-3 flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ configItem?.item_name }}</h3>
                        <p class="text-xs text-gray-500">Select variation and modifiers</p>
                    </div>
                    <button type="button" class="text-sm text-gray-500" @click="closeItemConfig">Close</button>
                </div>

                <div v-if="configVariations.length" class="mb-4">
                    <div class="mb-1 text-xs font-semibold uppercase text-gray-500">Variation</div>
                    <div class="space-y-1">
                        <label v-for="v in configVariations" :key="v.id" class="flex cursor-pointer items-center justify-between rounded border border-gray-200 px-2 py-1 text-sm dark:border-gray-700">
                            <span>{{ v.variation }}</span>
                            <span class="inline-flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ formatMoney(v.price) }}</span>
                                <input type="radio" name="pos-variation" :value="v.id" v-model="selectedVariationId" />
                            </span>
                        </label>
                    </div>
                </div>

                <div v-if="activeModifierGroups.length" class="mb-4 space-y-3">
                    <div class="text-xs font-semibold uppercase text-gray-500">Modifiers</div>
                    <div v-for="group in activeModifierGroups" :key="group.id" class="rounded border border-gray-200 p-2 dark:border-gray-700">
                        <div class="mb-1 text-sm font-medium">{{ group.name }}</div>
                        <div class="space-y-1">
                            <label v-for="opt in group.options" :key="opt.id" class="flex cursor-pointer items-center justify-between text-sm">
                                <span>{{ opt.name }}</span>
                                <span class="inline-flex items-center gap-2">
                                    <span class="text-xs text-gray-500">+{{ formatMoney(opt.price) }}</span>
                                    <input type="checkbox" :value="opt.id" v-model="selectedModifierOptionIds" />
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded border border-gray-300 px-3 py-1.5 text-sm" @click="closeItemConfig">Cancel</button>
                    <button type="button" class="rounded bg-skin-base px-3 py-1.5 text-sm text-white" @click="confirmAddConfiguredItem">Add Item</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const rootEl = document.getElementById('pos-app');
let parsedBootstrap = {};
try {
    parsedBootstrap = JSON.parse(rootEl?.dataset?.bootstrap || '{}');
} catch (error) {
    console.error('Failed to parse POS bootstrap payload', error);
    parsedBootstrap = {};
}
const bootstrap = ref(parsedBootstrap);

const search = ref('');
const menuId = ref(null);
const categoryId = ref(null);
const orderTypeId = ref(bootstrap.value.order_types?.[0]?.id ?? null);
const waiterId = ref(null);
const orderAction = ref('kot');
const openPaymentAfterBill = ref(true);
const cart = ref([]);
const saving = ref(false);
const syncingPending = ref(false);
const isOnline = ref(navigator.onLine);
const pendingOrders = ref([]);
const showItemConfigModal = ref(false);
const configItem = ref(null);
const selectedVariationId = ref(null);
const selectedModifierOptionIds = ref([]);

const CART_STORAGE_KEY = 'pos_vue_shell_cart';
const META_STORAGE_KEY = 'pos_vue_shell_meta';
const PENDING_STORAGE_KEY = 'pos_vue_shell_pending_orders';

const money = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatMoney = (val) => `${bootstrap.value.currency_symbol || '$'} ${money.format(Number(val || 0))}`;

const filteredItems = computed(() => {
    const q = search.value.toLowerCase();
    return (bootstrap.value.items || []).filter((item) => {
        const matchSearch = !q || String(item.item_name || '').toLowerCase().includes(q);
        const matchMenu = !menuId.value || Number(item.menu_id) === Number(menuId.value);
        const matchCategory = !categoryId.value || Number(item.item_category_id) === Number(categoryId.value);
        return matchSearch && matchMenu && matchCategory;
    });
});

const subtotal = computed(() => cart.value.reduce((sum, line) => sum + Number(line.amount || 0), 0));
const totalTax = computed(() => {
    const taxes = bootstrap.value.taxes || [];
    if (!taxes.length) {
        return 0;
    }

    return taxes.reduce((sum, tax) => {
        const percent = Number(tax.tax_percent || 0);
        return sum + ((subtotal.value * percent) / 100);
    }, 0);
});
const total = computed(() => subtotal.value + totalTax.value);

const configVariations = computed(() => configItem.value?.variations || []);

const activeModifierGroups = computed(() => {
    const item = configItem.value;
    if (!item) {
        return [];
    }

    const variationKey = selectedVariationId.value ? String(selectedVariationId.value) : null;

    if (variationKey && item.variation_modifier_groups && item.variation_modifier_groups[variationKey]) {
        return item.variation_modifier_groups[variationKey] || [];
    }

    return item.modifier_groups || [];
});

function persistCartState() {
    try {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart.value));
        localStorage.setItem(META_STORAGE_KEY, JSON.stringify({
            orderTypeId: orderTypeId.value,
            waiterId: waiterId.value,
            orderAction: orderAction.value,
            openPaymentAfterBill: openPaymentAfterBill.value,
        }));
    } catch (error) {
        console.error('Failed to persist cart state', error);
    }
}

function loadCartState() {
    try {
        const storedCart = localStorage.getItem(CART_STORAGE_KEY);
        const storedMeta = localStorage.getItem(META_STORAGE_KEY);
        const storedPending = localStorage.getItem(PENDING_STORAGE_KEY);

        if (storedCart) {
            const parsedCart = JSON.parse(storedCart);
            if (Array.isArray(parsedCart)) {
                cart.value = parsedCart;
            }
        }

        if (storedMeta) {
            const parsedMeta = JSON.parse(storedMeta);
            orderTypeId.value = parsedMeta?.orderTypeId ?? orderTypeId.value;
            waiterId.value = parsedMeta?.waiterId ?? null;
            orderAction.value = parsedMeta?.orderAction === 'bill' ? 'bill' : 'kot';
            openPaymentAfterBill.value = parsedMeta?.openPaymentAfterBill ?? true;
        }

        if (storedPending) {
            const parsedPending = JSON.parse(storedPending);
            if (Array.isArray(parsedPending)) {
                pendingOrders.value = parsedPending;
            }
        }
    } catch (error) {
        console.error('Failed to load cart state', error);
    }
}

function persistPendingOrders() {
    try {
        localStorage.setItem(PENDING_STORAGE_KEY, JSON.stringify(pendingOrders.value));
    } catch (error) {
        console.error('Failed to persist pending orders', error);
    }
}

function buildBatchPayload(lines, action = 'kot', openPayment = false) {
    return {
        action,
        open_payment: Boolean(openPayment),
        order_type_id: orderTypeId.value,
        waiter_id: waiterId.value,
        lines: lines.map((line) => ({
            menu_item_id: line.item_id,
            menu_item_variation_id: line.variation_id ?? null,
            qty: line.qty,
            note: line.note ?? null,
            modifier_option_quantities: line.modifier_option_quantities || {},
            combo_pack_id: line.combo_pack_id ?? null,
            combo_instance_key: line.combo_instance_key ?? null,
        })),
    };
}

function queuePendingOrder(payload) {
    pendingOrders.value.push({
        id: `pending_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
        createdAt: new Date().toISOString(),
        payload,
    });
    persistPendingOrders();
}

async function postOrder(payload) {
    return axios.post('/api/pos/orders', payload);
}

function openPaymentModal(orderId) {
    if (!orderId) {
        return;
    }

    if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
        window.Livewire.dispatch('showPaymentModal', { id: orderId });
    }
}

function lineKey(config) {
    const comboSegment = config.combo_instance_key ? `combo:${config.combo_instance_key}` : 'single';
    return `${comboSegment}:${config.item_id}:${config.variation_id ?? 'none'}:${Object.keys(config.modifier_option_quantities || {}).sort().join(',')}`;
}

function addItem(config) {
    const key = lineKey(config);
    const existing = cart.value.find((line) => line.key === key);

    if (existing) {
        existing.qty += 1;
        existing.amount = existing.qty * existing.unit_price;
        return;
    }

    cart.value.push({
        key,
        item_id: Number(config.item_id),
        item_name: config.item_name,
        variation_id: config.variation_id ?? null,
        variation_name: config.variation_name ?? null,
        modifier_option_quantities: config.modifier_option_quantities || {},
        modifier_labels: config.modifier_labels || [],
        combo_pack_id: config.combo_pack_id ?? null,
        combo_instance_key: config.combo_instance_key ?? null,
        is_combo_item: Boolean(config.combo_pack_id && config.combo_instance_key),
        unit_price: Number(config.unit_price || 0),
        qty: 1,
        amount: Number(config.unit_price || 0),
    });
}

function addComboPack(combo) {
    const instanceKey = `${combo.id}_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;

    for (const line of combo.items || []) {
        const qty = Number(line.quantity || 1);
        const unitPrice = Number(line.discounted_unit_price || 0);
        const originalUnit = Number(line.original_unit_price || unitPrice);

        const key = lineKey({
            item_id: line.menu_item_id,
            variation_id: line.menu_item_variation_id,
            combo_instance_key: instanceKey,
            modifier_option_quantities: {},
        });

        cart.value.push({
            key,
            item_id: Number(line.menu_item_id),
            item_name: line.item_name || 'Combo Item',
            variation_id: line.menu_item_variation_id ?? null,
            variation_name: line.variation_name || null,
            modifier_option_quantities: {},
            modifier_labels: [],
            combo_pack_id: Number(combo.id),
            combo_instance_key: instanceKey,
            is_combo_item: true,
            unit_price: unitPrice,
            original_unit_price: originalUnit,
            qty,
            amount: qty * unitPrice,
        });
    }
}

function openItemConfig(item) {
    configItem.value = item;
    selectedModifierOptionIds.value = [];
    selectedVariationId.value = item.variations?.length ? item.variations[0].id : null;

    const hasConfig = (item.variations_count || 0) > 0 || (item.modifier_groups_count || 0) > 0;

    if (!hasConfig) {
        addItem({
            item_id: Number(item.id),
            item_name: item.item_name,
            variation_id: null,
            variation_name: null,
            modifier_option_quantities: {},
            modifier_labels: [],
            unit_price: Number(item.price || 0),
        });
        return;
    }

    showItemConfigModal.value = true;
}

function closeItemConfig() {
    showItemConfigModal.value = false;
    configItem.value = null;
    selectedVariationId.value = null;
    selectedModifierOptionIds.value = [];
}

function confirmAddConfiguredItem() {
    if (!configItem.value) {
        return;
    }

    const item = configItem.value;
    const variation = (item.variations || []).find((v) => Number(v.id) === Number(selectedVariationId.value));
    const basePrice = Number(variation?.price ?? item.price ?? 0);

    const selectedSet = new Set((selectedModifierOptionIds.value || []).map((id) => Number(id)));
    const optionMap = {};
    const modifierLabels = [];
    let modifiersUnitTotal = 0;

    for (const group of activeModifierGroups.value || []) {
        for (const opt of group.options || []) {
            if (selectedSet.has(Number(opt.id))) {
                optionMap[Number(opt.id)] = 1;
                modifiersUnitTotal += Number(opt.price || 0);
                modifierLabels.push(String(opt.name || ''));
            }
        }
    }

    addItem({
        item_id: Number(item.id),
        item_name: item.item_name,
        variation_id: variation ? Number(variation.id) : null,
        variation_name: variation?.variation ?? null,
        modifier_option_quantities: optionMap,
        modifier_labels: modifierLabels,
        unit_price: basePrice + modifiersUnitTotal,
    });

    closeItemConfig();
}

function deltaQty(key, delta) {
    const line = cart.value.find((row) => row.key === key);
    if (!line) {
        return;
    }

    line.qty = Math.max(0, line.qty + Number(delta || 0));
    if (line.qty <= 0) {
        removeLine(key);
        return;
    }

    line.amount = line.qty * line.unit_price;
}

function removeLine(key) {
    const line = cart.value.find((row) => row.key === key);
    if (!line) {
        return;
    }

    if (line.combo_instance_key) {
        cart.value = cart.value.filter((row) => row.combo_instance_key !== line.combo_instance_key);
        return;
    }

    cart.value = cart.value.filter((row) => row.key !== key);
}

async function saveOrder() {
    if (cart.value.length === 0 || saving.value) {
        return;
    }

    saving.value = true;
    const snapshot = cart.value.map((line) => ({ ...line }));
    const action = orderAction.value === 'bill' ? 'bill' : 'kot';
    const openPayment = action === 'bill' ? openPaymentAfterBill.value : false;
    const payload = buildBatchPayload(snapshot, action, openPayment);

    try {
        if (!isOnline.value) {
            queuePendingOrder(payload);
            cart.value = [];
            persistCartState();
            return;
        }

        const response = await postOrder(payload);
        const orderId = response?.data?.data?.order_id;
        const shouldOpenPayment = Boolean(response?.data?.data?.should_open_payment_modal);

        if (shouldOpenPayment) {
            openPaymentModal(orderId);
        }

        cart.value = [];
        persistCartState();
    } catch (error) {
        console.error('POS Vue save hook failed', error);
        const status = Number(error?.response?.status || 0);
        const isClientError = status >= 400 && status < 500;
        const isNetworkError = error?.code === 'ERR_NETWORK' || !error?.response;

        if (isNetworkError) {
            queuePendingOrder(payload);
            cart.value = [];
            persistCartState();
            return;
        }

        const serverMessage = error?.response?.data?.message || (isClientError
            ? 'Unable to save order. Please review the order input and try again.'
            : 'Unable to save order right now. Please try again.');

        if (typeof window !== 'undefined' && typeof window.alert === 'function') {
            window.alert(serverMessage);
        }
    } finally {
        saving.value = false;
    }
}

async function syncPendingOrders() {
    if (syncingPending.value || !isOnline.value || pendingOrders.value.length === 0) {
        return;
    }

    syncingPending.value = true;
    const remaining = [];

    for (const pending of pendingOrders.value) {
        try {
            if (!pending?.payload) {
                continue;
            }

            await postOrder(pending.payload);
        } catch (error) {
            console.error('Failed syncing pending order', pending.id, error);
            remaining.push(pending);
        }
    }

    pendingOrders.value = remaining;
    persistPendingOrders();
    syncingPending.value = false;
}

const handleOnline = () => {
    isOnline.value = true;
};

const handleOffline = () => {
    isOnline.value = false;
};

watch(cart, persistCartState, { deep: true });
watch([orderTypeId, waiterId, orderAction, openPaymentAfterBill], persistCartState);

onMounted(() => {
    loadCartState();
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    if (isOnline.value && pendingOrders.value.length > 0) {
        syncPendingOrders();
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
});
</script>
