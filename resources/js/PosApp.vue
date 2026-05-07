<template>
    <div id="pos-container" class="overflow-x-hidden">
        <!-- Error Display for Missing Bootstrap Data -->
        <div v-if="!bootstrapData" class="p-6 bg-red-50 border border-red-200 rounded m-4">
            <h2 class="text-xl font-bold text-red-700 mb-2">POS Configuration Error</h2>
            <p class="text-red-600 mb-4">Bootstrap data is missing or failed to load.</p>
            <details class="text-sm text-red-600 bg-white p-3 rounded border border-red-200">
                <summary class="cursor-pointer font-semibold mb-2">Debug Info</summary>
                <pre class="whitespace-pre-wrap break-words">{{ debugInfo }}</pre>
            </details>
            <button @click="retryLoadBootstrap" class="mt-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Retry
            </button>
        </div>

        <!-- Loading Display -->
        <div v-else-if="isLoading" class="flex items-center justify-center h-screen">
            <div class="text-center">
                <div class="mb-4">
                    <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>
                <p class="text-gray-600">Loading POS...</p>
            </div>
        </div>

        <!-- Main POS Content -->
        <div v-else class="flex flex-col lg:flex-row lg:flex-nowrap flex-grow h-auto pt-6 min-w-0 overflow-x-hidden">
            <MenuPanel class="w-full lg:basis-[70%] lg:max-w-[70%] min-w-0" :search="search" :menu-id="menuId"
                :filter-categories="filterCategories" :menus="menus" :categories="categories" :items="contextualMenuItems"
                :combo-packs="comboPacks" :hide-menu-item-image-on-pos="hideMenuItemImageOnPos"
                :currency-symbol="currencySymbol" :contextual-price-resolver="resolveContextualPrice"
                @update:search="search = $event"
                @update:menuId="menuId = $event"
                @update:filterCategories="filterCategories = $event" @add-to-cart="handleAddToCart"
                @add-combo-to-cart="handleAddComboToCart" @reset="handleReset" />

            <OrderPanel class="w-full lg:basis-[30%] lg:max-w-[30%] min-w-0" :order-type="orderType"
                :order-number="orderNumber" :current-table="currentTable" :pax="pax" :waiter-id="waiterId"
                :waiters="waiters" :customer="customer" :order-types="orderTypes" :cart-items="cartItems" :taxes="taxes"
                :saving-action="savingAction" :extra-charges="extraCharges" :discount-amount="discountAmount"
                :discount-type="discountType" :discount-value="discountValue" :is-online="isOnline"
                :total-tax-amount="totalTaxAmount" :is-inclusive="false" :currency-symbol="currencySymbol"
                :order-status="orderStatus" :delivery-platforms="deliveryPlatforms"
                :selected-delivery-app="selectedDeliveryApp" :current-user="currentUser" :can-edit-waiter="canEditWaiter"
                :set-as-default-order-type="setAsDefaultOrderType" :default-order-type-id="defaultOrderTypeId"
                :delivery-executives="deliveryExecutives"
                :selected-delivery-executive="selectedDeliveryExecutive" :delivery-fee="deliveryFee"
                :is-linked-order-mode="isLinkedOrderMode" :is-new-kot-mode="isNewKotMode"
                :order-lifecycle-status="orderLifecycleStatus"
                :order-permissions="orderPermissions" :kot-groups="kotGroups"
                :allow-custom-order-extras="allowCustomOrderExtras" :custom-extras="customExtras"
                :delivery-address="deliveryAddress" :customer-phone="customerPhone"
                :customer-lat="customerLat" :customer-lng="customerLng"
                :branch-lat="branchLat" :branch-lng="branchLng"
                :kot-module-enabled="kotModuleEnabled"
                :modifier-options="modifierOptions"
                :tip-amount="tipAmount"
                :pickup-date-time="pickupDateTime"
                :order-note="orderNote"
                :reward-point-discount="rewardPointDiscount"
                :reward-points-redeemed="rewardPointsRedeemed"
                :reward-points-earned="rewardPointsEarned"
                :reward-points-available="rewardPointsAvailable"
                :reward-display-name="rewardDisplayName"
                :reward-settings-enabled="rewardSettingsEnabled"
                :reward-max-redeemable="rewardMaxRedeemable"
                :reward-amount-per-point="rewardAmountPerPoint"
                :can-redeem-reward-points="canRedeemRewardPoints"
                @update:orderType="orderType = $event"
                @show-add-customer="showAddCustomerModal = true" @remove-customer="handleRemoveCustomer"
                @select-table="handleSelectTable" @remove-table="handleRemoveTable" @update:pax="pax = $event" @update:waiterId="handleWaiterUpdate"
                @update:orderStatus="handleOrderStatusUpdate" @add-note="handleAddNote"
                @update:selectedDeliveryExecutive="handleDeliveryExecutiveUpdate"
                @update:deliveryFee="handleDeliveryFeeUpdate"
                @update-quantity="handleUpdateQuantity" @update:selectedDeliveryApp="selectedDeliveryApp = $event"
                @update:setAsDefaultOrderType="setAsDefaultOrderType = $event" @increase-quantity="handleIncreaseQuantity"
                @update:defaultOrderTypeId="defaultOrderTypeId = $event"
                @decrease-quantity="handleDecreaseQuantity" @remove-item="handleRemoveItem" @save-order="handleSaveOrder"
                @open-payment="handleOpenPayment" @delete-order="handleDeleteOrder"
                @new-kot="handleNewKot"
                @request-cancel-order="handleRequestCancelOrder"
                @update:extraCharges="extraCharges = $event" @apply-discount="handleApplyDiscount"
                @remove-discount="handleRemoveDiscount"
                @remove-extra-charge="handleRemoveExtraCharge"
                @update:pickupDateTime="handlePickupDateTimeUpdate"
                @remove-kot-item="handleRemoveKotItem"
                @remove-kot-combo-group="handleRemoveKotComboGroup"
                @reduce-kot-item="handleReduceKotItem"
                @print-receipt="handlePrintReceipt"
                @add-custom-extra="handleAddCustomExtra"
                @remove-custom-extra="handleRemoveCustomExtra"
                @update-custom-extra="handleUpdateCustomExtra"
                @apply-reward-redemption="handleApplyRewardRedemption"
                @remove-reward-redemption="handleRemoveRewardRedemption"
                :order="order" />
        </div>

        <!-- Modals -->
        <ReservationModal :show="showReservationModal" :reservation="reservation" @close="showReservationModal = false"
            @confirm-same="handleConfirmSameCustomer" @confirm-different="handleConfirmDifferentCustomer" />

        <TableChangeModal :show="showTableChangeConfirmationModal" :current-table="currentTable" :new-table="newTable"
            @close="showTableChangeConfirmationModal = false" @confirm="handleConfirmTableChange" />

        <AddCustomerModal :show="showAddCustomerModal" :customer="customer" @close="showAddCustomerModal = false"
            @save="handleSaveCustomer" />

        <AddNoteModal :show="showAddNoteModal" :note="orderNote" @close="showAddNoteModal = false" @save="handleSaveNote" />

        <CancelOrderModal :show="showCancelOrderModal" :reasons="cancelReasons" @close="showCancelOrderModal = false"
            @save="handleSaveCancelOrder" />
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import MenuPanel from "./components/pos/MenuPanel.vue";
import OrderPanel from "./components/pos/OrderPanel.vue";
import ReservationModal from "./components/pos/ReservationModal.vue";
import TableChangeModal from "./components/pos/TableChangeModal.vue";
import AddCustomerModal from "./components/pos/AddCustomerModal.vue";
import AddNoteModal from "./components/pos/AddNoteModal.vue";
import CancelOrderModal from "./components/pos/CancelOrderModal.vue";
import { useOfflineMode } from "./composables/useOfflineMode.js";
import { showPosAlert, showPosConfirm } from "./utils/posAlerts.js";

// Generate unique tab ID to avoid concurrent increment collisions
const tabId = ref('tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9));

// Offline mode setup
const {
    isOnline,
    pendingOperations,
    queueOperation,
    saveCart: saveCartToStorage,
    loadCart: loadCartFromStorage,
    clearCustomer: clearCustomerFromStorage,
    syncPendingOperations,
    offlineApiCall,
} = useOfflineMode();

const getUrlParams = () => {
    const searchParams = new URLSearchParams(window.location.search);
    const pathname = window.location.pathname;
    const pathParts = pathname.split("/").filter((p) => p);
    const orderIndex = pathParts.indexOf("order");
    const kotIndex = pathParts.indexOf("kot");
    const resolvedIndex = orderIndex !== -1 ? orderIndex : kotIndex;
    const routeOrderId =
        resolvedIndex !== -1 && pathParts[resolvedIndex + 1]
            ? pathParts[resolvedIndex + 1]
            : null;
    const routeMode =
        orderIndex !== -1
            ? "order"
            : kotIndex !== -1
                ? "kot"
                : "new";

    return {
        orderId: searchParams.get("order_id") || routeOrderId,
        mode: searchParams.get("mode") || routeMode,
        showOrderDetail:
            searchParams.get("show-order-detail") === "true"
            || searchParams.get("show_order_detail") === "true",
    };
};

const params = getUrlParams();
const orderId = ref(params.orderId);
const mode = ref(params.mode);
const showOrderDetailMode = ref(!!params.showOrderDetail);

const getBootstrapData = () => {
    const scriptEl = document.getElementById("pos-app-bootstrap");
    if (scriptEl?.textContent) {
        const raw = String(scriptEl.textContent).trim();
        if (raw) {
            try {
                return JSON.parse(raw);
            } catch (error) {
                console.error("Failed to parse #pos-app-bootstrap JSON:", error);
            }
        }
    }
    const mountEl = document.getElementById("pos-app");
    if (!mountEl) {
        return null;
    }
    try {
        const attr = mountEl.getAttribute("data-bootstrap");
        return attr ? JSON.parse(attr) : null;
    } catch (error) {
        console.error("Failed to parse POS data-bootstrap attribute:", error);
        return null;
    }
};

const loadBootstrapFromAjax = async () => {
    try {
        const { data } = await axios.get("/ajax/pos/bootstrap", {
            headers: { Accept: "application/json" },
        });
        if (data && typeof data === "object" && !Array.isArray(data)) {
            return data;
        }
    } catch (error) {
        console.error("Failed to load POS bootstrap from /ajax/pos/bootstrap:", error);
    }
    return null;
};

const bootstrapData = ref(getBootstrapData());
const initialOrderData = computed(() => bootstrapData.value?.initial_order || null);
const initialTableData = computed(() => bootstrapData.value?.initial_table || null);

// Diagnostics - If bootstrap data is available inline, we're not loading
const isLoading = ref(!bootstrapData.value);

// Log bootstrap data for debugging
console.log("POS Bootstrap Data:", bootstrapData.value);

const debugInfo = computed(() => {
    return JSON.stringify({
        bootstrapDataLoaded: !!bootstrapData.value,
        bootstrapDataKeys: bootstrapData.value ? Object.keys(bootstrapData.value) : [],
        menuCount: menus.value.length,
        categoryCount: categories.value.length,
        menuItemCount: menuItems.value.length,
        orderTypesCount: orderTypes.value.length,
        waitersCount: waiters.value.length,
        currentUrl: window.location.href,
        elementId: document.getElementById("pos-app") ? "FOUND" : "NOT FOUND",
    }, null, 2);
});

const retryLoadBootstrap = async () => {
    bootstrapData.value = getBootstrapData();
    if (!bootstrapData.value) {
        bootstrapData.value = await loadBootstrapFromAjax();
    }
    if (bootstrapData.value) {
        loadRestaurantData();
        loadMenuData();
    }
};
const search = ref("");
const menuId = ref(null);
const filterCategories = ref(null);

// Menu data (to be fetched from API)
const menus = ref([]);
const categories = ref([]);
const menuItems = ref([]);
const comboPacks = ref([]);
/** Mirrors legacy `restaurant()->hide_menu_item_image_on_pos` (pos/menu.blade.php). */
const hideMenuItemImageOnPos = ref(false);
const availableTaxes = ref([]);
const orderTypes = ref([]);
const deliveryPlatforms = ref([]);
const restaurant = ref(null);
// Use bootstrap currency on first render — default `ref("$")` caused Subtotal/Total
// to flash "$" for linked orders until `loadRestaurantData()` ran after `await loadOrderData()`.
const currencySymbol = ref(
    (() => {
        const s = bootstrapData.value?.currency_symbol;
        if (s != null && String(s).trim() !== "") {
            return String(s);
        }
        return "$";
    })()
);
const kotModuleEnabled = ref(true); // Gated by KOT module subscription
const modifierOptions = ref({}); // Flat map: { [optionId]: { name, price } }

// Order data
const orderType = ref("Dine In");
const orderNumber = ref("");
const pax = ref(1);
const waiterId = ref(null);
const waiters = ref([]);
const currentUser = ref(null);
const canEditWaiter = ref(true);
const cartItems = ref([]);
const taxes = ref([]);
const savingAction = ref(null); // Track which action is being saved: 'kot', 'bill', 'bill_payment', etc.
const extraCharges = ref([]);
const discountAmount = ref(0);
const discountType = ref("");
const discountValue = ref(0);
const order = ref(null);
const orderTypeId = ref(null);
const selectedDeliveryApp = ref("default");
const setAsDefaultOrderType = ref(false);
const defaultOrderTypeId = ref(null);
const customerId = ref(null);
const orderStatus = ref("");
const deliveryExecutives = ref([]);
const selectedDeliveryExecutive = ref("");
const deliveryFee = ref(0);
const pickupDateTime = ref(""); // For Pickup order type
const tipAmount = ref(0); // Loaded from saved order
const orderLifecycleStatus = ref("");
// Legacy parity (Pos.php::$orderExtras): per-order custom extras rows.
// Each row is { amount: number, note: string }. Persisted via order_extras
// when the restaurant setting allow_custom_order_extras is enabled.
const allowCustomOrderExtras = ref(false);
const customExtras = ref([]);
// Reward Points state
const rewardPointDiscount = ref(0);
const rewardPointsRedeemed = ref(0);
const rewardPointsEarned = ref(0);
const rewardPointsAvailable = ref(0);
const rewardDisplayName = ref('Reward');
const rewardSettingsEnabled = ref(false);
const rewardMaxRedeemable = ref(0);
const rewardAmountPerPoint = ref(1);
const canRedeemRewardPoints = ref(false);
const orderPermissions = ref({
    can_update_order: false,
    can_delete_order: false,
    can_edit_billed_order: false,
    can_delete_kot_item: false,
});
const kotGroups = ref([]);
const deliveryAddress = ref("");
const customerPhone = ref("");
const customerLat = ref(null);
const customerLng = ref(null);
const branchLat = ref(null);
const branchLng = ref(null);
const cancelReasons = ref([]);
// Legacy parity (Pos.php mount + pos.blade.php):
//   /pos/kot/{id} WITHOUT ?show-order-detail=true is the "New KOT" flow — the
//   existing order is contextually loaded (customer/waiter/type), but the cart
//   starts empty and saves are append-only. It is NOT a linked-order view, so
//   we render the regular cart UI and only the KOT action buttons.
const isNewKotMode = computed(() => {
    if (!orderId.value) {
        return false;
    }

    return mode.value === "kot" && !showOrderDetailMode.value;
});

const isLinkedOrderMode = computed(() => {
    if (!orderId.value) {
        return false;
    }

    if (isNewKotMode.value) {
        return false;
    }

    return !!showOrderDetailMode.value || mode.value === "order" || mode.value === "kot";
});
// Modal state
const showReservationModal = ref(false);
const showTableChangeConfirmationModal = ref(false);
const showAddCustomerModal = ref(false);
const showCancelOrderModal = ref(false);
const reservation = ref({
    customerName: null,
    time: null,
});
const customer = ref({
    name: "",
    email: "",
    phone: "",
    phone_code: "",
    address: "",
});

const getEmptyCustomer = () => ({
    name: "",
    email: "",
    phone: "",
    phone_code: "",
    address: "",
});

const normalizeCustomerPayload = (payloadCustomer) => {
    if (!payloadCustomer?.id) {
        return getEmptyCustomer();
    }

    return {
        id: Number(payloadCustomer.id),
        name: payloadCustomer.name || "",
        email: payloadCustomer.email || "",
        phone: payloadCustomer.phone || "",
        phone_code: payloadCustomer.phone_code || "",
        address: payloadCustomer.address || payloadCustomer.delivery_address || "",
        delivery_address: payloadCustomer.delivery_address || payloadCustomer.address || "",
    };
};
const currentTable = ref("");
const currentTableId = ref(null);
const newTable = ref("");
const newTableId = ref(null);
const newTableActiveOrderId = ref(null);
const showAddNoteModal = ref(false);
const orderNote = ref("");

// Play beep sound when item is added to cart
const playBeepSound = () => {
    try {
        const audio = new Audio("/sound/sound_beep-29.mp3");
        audio.volume = 0.5;
        audio.play().catch((error) => {
            console.log("Audio play failed:", error);
        });
    } catch (error) {
        console.log("Error playing beep sound:", error);
    }
};

// Methods
const nextComboInstanceIndex = (packId) => {
    const pid = Number(packId);
    let max = 0;
    const prefix = `combo_${pid}_`;
    for (const ci of cartItems.value) {
        const k = ci.combo_instance_key;
        if (!k || typeof k !== "string" || !k.startsWith(prefix)) {
            continue;
        }
        const suffix = k.slice(prefix.length);
        const n = parseInt(suffix, 10);
        if (!Number.isNaN(n) && n > max) {
            max = n;
        }
    }
    return max + 1;
};

const handleAddComboToCart = async (comboPackId) => {
    const pid = Number(comboPackId);
    if (!pid) {
        return;
    }

    try {
        const selectedType = resolveOrderType(orderType.value);
        const orderTypeId = selectedType?.id || null;
        const slug = normalizeOrderTypeSlug(selectedType?.slug || orderType.value);
        const params = new URLSearchParams();
        if (orderTypeId) {
            params.set("order_type_id", String(orderTypeId));
        }
        if (slug === "delivery" && selectedDeliveryApp.value && selectedDeliveryApp.value !== "default") {
            params.set("delivery_app_id", String(selectedDeliveryApp.value));
        }

        const qs = params.toString();
        const url = `/api/pos/combo-packs/${pid}/preview${qs ? `?${qs}` : ""}`;
        const res = await axios.get(url);

        if (!res.data?.success) {
            showPosAlert("error", res.data?.message || "Unable to add combo pack.");
            return;
        }

        const packName = res.data.name || "Combo Pack";
        const previewLines = Array.isArray(res.data.lines) ? res.data.lines : [];
        if (previewLines.length === 0) {
            showPosAlert("error", "This combo pack has no items.");
            return;
        }

        const conflictNames = [];
        for (const line of previewLines) {
            const mid = Number(line.menu_item_id);
            const vid = Number(line.menu_item_variation_id || 0);
            const regKey = createCartLineKey(mid, vid, 0);
            const existing = cartItems.value.find((ci) => getCartLineKey(ci) === regKey);
            if (existing && !existing.combo_pack_id) {
                const nm = existing.name || line.item_name || "Item";
                if (!conflictNames.includes(nm)) {
                    conflictNames.push(nm);
                }
            }
        }
        if (conflictNames.length > 0) {
            showPosAlert(
                "info",
                `${conflictNames.join(", ")} ${conflictNames.length === 1 ? "is" : "are"} already in the cart as regular items; still adding as combo.`,
            );
        }

        const instanceNum = nextComboInstanceIndex(pid);
        const instanceKey = `combo_${pid}_${instanceNum}`;

        for (const line of previewLines) {
            const mid = Number(line.menu_item_id);
            const vid = Number(line.menu_item_variation_id || 0);
            const qty = Math.max(1, parseInt(line.qty, 10) || 1);
            const unitPrice = Number(line.unit_price || 0);
            const comboDiscount = Number(line.combo_discount_per_unit || 0);
            const originalUnit = Number(
                line.combo_original_unit_price ??
                    line.original_unit_price ??
                    unitPrice + comboDiscount
            );
            const lineKey = `combo_${pid}_${instanceNum}_${mid}_${vid}_0`;
            const displayName = [line.item_name, line.variation_name].filter(Boolean).join(" — ") || "Item";

            cartItems.value.push({
                id: mid,
                menu_item_id: mid,
                name: displayName,
                price: unitPrice,
                base_unit_price: unitPrice,
                quantity: qty,
                variant_id: vid,
                modifier_id: 0,
                line_key: lineKey,
                note: "",
                combo_pack_id: pid,
                combo_instance_key: instanceKey,
                combo_pack_name: packName,
                combo_discount: comboDiscount,
                combo_original_unit_price: originalUnit,
                modifier_option_quantities: {},
            });
        }

        saveCartToStorage(cartItems.value);
        playBeepSound();
        showPosAlert("success", "Combo added to cart.");
    } catch (error) {
        const msg = error.response?.data?.message || error.message || "Failed to add combo pack.";
        showPosAlert("error", msg);
    }
};

const handleAddToCart = async (
    itemId,
    variantId = 0,
    modifierId = 0,
    modifierOptionQuantities = {}
) => {
    const normalizedItemId = Number(itemId);
    const normalizedVariantId = Number(variantId || 0);
    const normalizedModifierId = Number(modifierId || 0);
    const modifierMap = normalizeModifierQuantities(modifierOptionQuantities);
    const modifierSignature = buildModifierSignature(modifierMap);
    const lineKey = createCartLineKey(
        normalizedItemId,
        normalizedVariantId,
        normalizedModifierId,
        modifierSignature
    );

    const item = menuItems.value.find((i) => Number(i.id) === normalizedItemId);
    console.log("addCartItems:", itemId, variantId, modifierId, modifierMap);

    if (!item) {
        console.error("Item not found with id:", itemId);
        return;
    }

    const selectedVariation =
        normalizedVariantId && Array.isArray(item.variations)
            ? item.variations.find((v) => Number(v.id) === normalizedVariantId) || null
            : null;

    let basePrice = resolveContextualPrice(item);
    if (selectedVariation) {
        basePrice = resolveContextualPrice(item, selectedVariation.id);
    }
    const modifierUnitTotal = computeModifierUnitTotal(
        item,
        normalizedVariantId,
        modifierMap
    );
    const unitPrice = Number((Number(basePrice || 0) + modifierUnitTotal).toFixed(2));

    const existingItem = cartItems.value.find(
        (ci) => getCartLineKey(ci) === lineKey
    );
    if (existingItem) {
        existingItem.quantity++;
        // Keep base/unit pricing consistent when nudging qty up.
        existingItem.base_unit_price = unitPrice;
        existingItem.price = unitPrice;
        existingItem.modifier_option_quantities = modifierMap;
    } else {
        const newCartItem = {
            id: normalizedItemId,
            menu_item_id: normalizedItemId,
            name: [item.item_name || item.name || "Unknown Item", selectedVariation?.variation]
                .filter(Boolean)
                .join(" — "),
            price: unitPrice,
            base_unit_price: unitPrice,
            quantity: 1,
            variant_id: normalizedVariantId,
            modifier_id: normalizedModifierId,
            line_key: lineKey,
            modifier_option_quantities: modifierMap,
        };
        cartItems.value.push(newCartItem);
    }
    saveCartToStorage(cartItems.value);
    playBeepSound();
};

const createCartLineKey = (
    itemId,
    variantId = 0,
    modifierId = 0,
    modifierSignature = ""
) => {
    const sigPart = modifierSignature ? `:${modifierSignature}` : "";
    return `${itemId}:${Number(variantId || 0)}:${Number(modifierId || 0)}${sigPart}`;
};

/**
 * Coerce a raw `modifier_option_quantities` payload (from the modal or an
 * existing cart line) into a clean `{ [optionId:number]: qty:number }` map
 * with positive integer quantities only. Mirrors legacy
 * Pos::normalizeModifierQuantities so the line key stays stable across paths.
 */
const normalizeModifierQuantities = (raw) => {
    const out = {};
    if (!raw) return out;
    if (Array.isArray(raw)) {
        raw.forEach((id) => {
            const k = Number(id);
            if (k > 0) out[k] = 1;
        });
        return out;
    }
    if (typeof raw === "object") {
        Object.keys(raw).forEach((k) => {
            const optionId = Number(k);
            const qty = Number(raw[k] || 0);
            if (optionId > 0 && qty > 0) out[optionId] = qty;
        });
    }
    return out;
};

/**
 * Build a deterministic signature for a modifier selection so two cart lines
 * with the same item+variation but different modifier sets stay separate
 * (legacy Pos.php uses md5 of the same shape; we keep it readable here).
 */
const buildModifierSignature = (map) => {
    const ids = Object.keys(map || {})
        .map((k) => Number(k))
        .filter((n) => n > 0)
        .sort((a, b) => a - b);
    if (!ids.length) return "";
    return ids.map((id) => `${id}x${Number(map[id] || 0)}`).join("|");
};

/**
 * Sum of (modifier option price × qty) across the selected map. Looks up
 * prices from the menu item's `modifier_groups` first, then
 * `variation_modifier_groups[variantId]`, falling back to the global
 * `modifierOptions` flat map for compatibility.
 */
const computeModifierUnitTotal = (item, variantId, map) => {
    if (!map || !Object.keys(map).length) return 0;
    const priceById = {};

    const harvest = (groups) => {
        if (!Array.isArray(groups)) return;
        groups.forEach((g) => {
            (g.options || []).forEach((opt) => {
                priceById[Number(opt.id)] = Number(opt.price || 0);
            });
        });
    };

    if (item) {
        harvest(item.modifier_groups);
        if (variantId) {
            const map2 = item.variation_modifier_groups || {};
            harvest(map2[String(variantId)]);
        }
    }

    let total = 0;
    Object.keys(map).forEach((k) => {
        const id = Number(k);
        const qty = Number(map[k] || 0);
        if (qty <= 0) return;
        const price = priceById[id] ?? Number(modifierOptions.value?.[id]?.price || 0);
        total += price * qty;
    });
    return Number(total.toFixed(2));
};

const getCartLineKey = (item) => {
    if (!item) {
        return null;
    }

    return (
        item.line_key ||
        createCartLineKey(item.id, item.variant_id || 0, item.modifier_id || 0)
    );
};

const parseComboPackIdFromInstanceKey = (instanceKey) => {
    if (!instanceKey || typeof instanceKey !== "string") {
        return null;
    }
    const m = instanceKey.match(/^combo_(\d+)_/);
    return m ? Number(m[1]) : null;
};

const findCartLine = (itemId, variantId = 0, modifierId = 0) => {
    return cartItems.value.find(
        (ci) =>
            getCartLineKey(ci) === itemId ||
            (ci.id === itemId &&
                Number(ci.variant_id || 0) === Number(variantId || 0) &&
                Number(ci.modifier_id || 0) === Number(modifierId || 0))
    );
};

const syncCartLinePrice = (item) => {
    if (!item) {
        return;
    }

    if (item.combo_pack_id) {
        return;
    }

    const currentPrice = Number(item.price || 0);
    const baseUnitPrice = Number(item.base_unit_price || currentPrice || 0);
    item.base_unit_price = baseUnitPrice;
    item.price = baseUnitPrice;
};

const handleReset = () => {
    search.value = "";
    menuId.value = null;
    filterCategories.value = null;
};

const handleChangeOrderType = () => {
    // TODO: Implement order type change
    console.log("changeOrderType");
};

const normalizeOrderTypeSlug = (value) => {
    const normalized = String(value || "")
        .trim()
        .toLowerCase()
        .replace(/\s+/g, "_");

    if (normalized === "dine_in" || normalized === "dine in") {
        return "dine_in";
    }

    if (normalized === "pickup") {
        return "pickup";
    }

    if (normalized === "delivery") {
        return "delivery";
    }

    return "dine_in";
};

const resolveOrderType = (value) => {
    const target = normalizeOrderTypeSlug(value);
    if (!value) {
        console.warn("resolveOrderType called with empty value");
        return null;
    }
    const typeList = orderTypes.value && orderTypes.value.length > 0 ? orderTypes.value : (bootstrapData.value?.order_types || []);
    const found = typeList.find((type) => normalizeOrderTypeSlug(type.slug) === target);
    if (!found) {
        console.warn(`Order type not found for: ${value}. Available types:`, typeList.map(t => t.slug));
    }
    return found || null;
};

const getDeliveryPlatform = (deliveryAppId) => {
    const appId = Number(deliveryAppId || 0);
    if (!appId) {
        return null;
    }

    return deliveryPlatforms.value.find(
        (platform) => Number(platform.id) === appId
    ) || null;
};

const applyDeliveryCommission = (basePrice, deliveryAppId) => {
    const platform = getDeliveryPlatform(deliveryAppId);
    if (!platform) {
        return Number(basePrice || 0);
    }

    const commissionType = String(platform.commission_type || "fixed").toLowerCase();
    const commissionValue = Number(platform.commission_value || 0);
    const numericBasePrice = Number(basePrice || 0);

    if (commissionValue <= 0 || numericBasePrice <= 0) {
        return numericBasePrice;
    }

    if (commissionType === "percent") {
        return numericBasePrice + (numericBasePrice * commissionValue) / 100;
    }

    return numericBasePrice + commissionValue;
};

const resolveContextualPrice = (item, variationId = null) => {
    if (!item) {
        return 0;
    }

    const selectedType = resolveOrderType(orderType.value);
    const orderTypeId = Number(selectedType?.id || 0) || null;
    const normalizedType = normalizeOrderTypeSlug(selectedType?.slug || orderType.value);
    const deliveryAppId =
        normalizedType === "delivery" && selectedDeliveryApp.value !== "default"
            ? Number(selectedDeliveryApp.value || 0) || null
            : null;

    const pricingRows = variationId
        ? (item.variations || []).find((variation) => Number(variation.id) === Number(variationId))?.pricing_rows || []
        : item.pricing_rows || [];

    const exact = pricingRows.find((row) => {
        const rowOrderTypeId = row.order_type_id ? Number(row.order_type_id) : null;
        const rowDeliveryAppId = row.delivery_app_id ? Number(row.delivery_app_id) : null;

        return rowOrderTypeId === orderTypeId && rowDeliveryAppId === deliveryAppId;
    });

    if (exact) {
        return Number(exact.final_price || 0);
    }

    const relaxedDelivery = pricingRows.find((row) => {
        const rowOrderTypeId = row.order_type_id ? Number(row.order_type_id) : null;
        const rowDeliveryAppId = row.delivery_app_id ? Number(row.delivery_app_id) : null;

        return rowOrderTypeId === orderTypeId && rowDeliveryAppId === null;
    });

    if (relaxedDelivery) {
        const relaxedPrice = Number(relaxedDelivery.final_price || 0);
        return deliveryAppId ? applyDeliveryCommission(relaxedPrice, deliveryAppId) : relaxedPrice;
    }

    const orderTypeOnly = pricingRows.find((row) => {
        const rowOrderTypeId = row.order_type_id ? Number(row.order_type_id) : null;
        return rowOrderTypeId === orderTypeId;
    });

    if (orderTypeOnly) {
        return Number(orderTypeOnly.final_price || 0);
    }

    const fallbackBasePrice = variationId
        ? Number((item.variations || []).find((variation) => Number(variation.id) === Number(variationId))?.price || 0)
        : Number(item.price || item.contextual_price || 0);

    return deliveryAppId ? applyDeliveryCommission(fallbackBasePrice, deliveryAppId) : fallbackBasePrice;
};

const contextualMenuItems = computed(() => {
    return menuItems.value.map((item) => ({
        ...item,
        contextual_price: resolveContextualPrice(item),
        variations: Array.isArray(item.variations)
            ? item.variations.map((variation) => ({
                ...variation,
                contextual_price: resolveContextualPrice(item, variation.id),
            }))
            : [],
    }));
});

const handleSelectTable = (table) => {
    const selectedTableCode = table.table_code;
    const selectedTableId = table.id;
    const selectedActiveOrderId = table.active_order_id ? Number(table.active_order_id) : null;

    // Show confirmation modal if there's an existing table that's different
    if (currentTable.value && currentTable.value !== selectedTableCode) {
        newTable.value = selectedTableCode;
        newTableId.value = selectedTableId;
        newTableActiveOrderId.value = selectedActiveOrderId;
        showTableChangeConfirmationModal.value = true;
    } else {
        applySelectedTable(selectedTableCode, selectedTableId, selectedActiveOrderId);
    }
};

/**
 * Remove the current table assignment from the order.
 * Clears local state, calls the API to detach the table from any existing
 * order, and releases the table's session lock so it becomes available.
 */
const handleRemoveTable = async () => {
    if (!currentTable.value && !currentTableId.value) return;

    const confirmed = await showPosConfirm(
        `Remove table ${currentTable.value || ""} from this order?`,
        {
            icon: "warning",
            confirmButtonText: "Remove Table",
            cancelButtonText: "Cancel",
        }
    );
    if (!confirmed) return;

    const previousTableId = currentTableId.value;
    const previousTableCode = currentTable.value;

    // Clear local state immediately
    currentTable.value = "";
    currentTableId.value = null;

    const activeOrderId = orderId.value ? Number(orderId.value) : null;

    if (activeOrderId) {
        try {
            // Detach table from the persisted order (sets orders.table_id = null,
            // releases the old table's available_status and session lock server-side).
            await axios.post(`/api/pos/orders/${activeOrderId}/table`, {
                table_id: null,
            });

            await loadOrderData(activeOrderId);

            showPosAlert("success", `Table ${previousTableCode} removed from order.`);
        } catch (error) {
            // Revert on failure
            currentTable.value = previousTableCode;
            currentTableId.value = previousTableId;
            const errorMessage =
                error?.response?.data?.message ||
                error?.message ||
                "Failed to remove table";
            showPosAlert("error", errorMessage);
            return;
        }
    } else {
        // No persisted order — just release the user-lock on the table
        if (previousTableId) {
            try {
                await axios.post(`/api/pos/tables/${previousTableId}/unlock`);
            } catch (e) {
                // Best-effort; the lock will timeout anyway
                console.warn("Failed to unlock previous table:", e);
            }
        }
        showPosAlert("success", `Table ${previousTableCode} removed.`);
    }
};

const handleAddNote = async (noteData) => {
    // If noteData is an object with id and note, it's for a cart item
    if (noteData && typeof noteData === "object" && noteData.id) {
        const activeOrderId = resolveActiveOrderId();
        if (activeOrderId && (noteData.kot_item_id || noteData.order_item_id)) {
            try {
                await axios.post(`/api/pos/orders/${activeOrderId}/items/note`, {
                    kot_item_id: noteData.kot_item_id || null,
                    order_item_id: noteData.order_item_id || null,
                    note: noteData.note || "",
                });
            } catch (error) {
                const message = error?.response?.data?.message || "Failed to update item note.";
                console.error("Error updating linked order item note:", error);
                showPosAlert("error", message);
                return;
            }

            try {
                await loadOrderData(activeOrderId);
            } catch (error) {
                console.error("Note saved but failed to refresh order:", error);
                showPosAlert(
                    "warning",
                    "Note saved but failed to refresh order. Try reopening the order if the screen looks stale."
                );
            }
            return;
        }

        // Find the cart item and update its note
        const cartItem = cartItems.value.find(
            (item) => (item.line_key || item.id) === (noteData.line_key || noteData.id)
        );
        if (cartItem) {
            cartItem.note = noteData.note || "";
            // Save cart to localStorage
            saveCartToStorage(cartItems.value);
            console.log("Note added to cart item:", cartItem.id, cartItem.note);
        }
    } else {
        // Otherwise, it's for the order note (the button at the top)
        showAddNoteModal.value = true;
    }
};

const loadCancelReasons = async () => {
    try {
        const response = await axios.get("/api/pos/cancel-reasons");
        cancelReasons.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error("Failed to load cancel reasons:", error);
        cancelReasons.value = [];
    }
};

const handleRequestCancelOrder = () => {
    showCancelOrderModal.value = true;
};

const handleSaveCancelOrder = async ({ cancelReasonId, cancelReasonText }) => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId) {
        return;
    }

    if (!cancelReasonId && !cancelReasonText) {
        showPosAlert("error", "Please select a cancel reason or enter cancel reason text");
        return;
    }

    try {
        const response = await axios.post(`/api/pos/orders/${activeOrderId}/status`, {
            order_status: "cancelled",
            cancel_reason_id: cancelReasonId,
            cancel_reason_text: cancelReasonText,
        });

        if (response.data?.success) {
            showCancelOrderModal.value = false;
            showPosAlert("success", response.data?.message || "Order cancelled successfully");
            clearCartAfterSave();
            window.location.href = "/pos";
        }
    } catch (error) {
        console.error("Error cancelling order:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to cancel order");
    }
};

const handleSaveNote = (note) => {
    orderNote.value = note;
    console.log("Order note saved:", note);
};

const handleIncreaseQuantity = (itemId) => {
    const item = findCartLine(itemId);
    if (item && item.combo_pack_id) {
        return;
    }
    if (item) {
        item.quantity++;
        syncCartLinePrice(item);
        // Save cart to localStorage
        saveCartToStorage(cartItems.value);
    }
};

const handleDecreaseQuantity = (itemId) => {
    const item = findCartLine(itemId);
    if (item && item.combo_pack_id) {
        handleRemoveItem(itemId);
        return;
    }
    if (item && item.quantity > 1) {
        item.quantity--;
        syncCartLinePrice(item);
    } else if (item) {
        cartItems.value = cartItems.value.filter(
            (ci) => getCartLineKey(ci) !== itemId
        );
    }
    // Save cart to localStorage
    saveCartToStorage(cartItems.value);
};

const handleUpdateQuantity = (quantityData) => {
    const item = findCartLine(
        quantityData.line_key || quantityData.id,
        quantityData.variant_id,
        quantityData.modifier_id
    );
    if (item && item.combo_pack_id) {
        return;
    }
    if (item) {
        const newQty = parseInt(quantityData.quantity, 10);
        if (newQty > 0) {
            item.quantity = newQty;
            syncCartLinePrice(item);
            // Save cart to localStorage
            saveCartToStorage(cartItems.value);
        } else {
            // Remove item if quantity is 0 or less
            cartItems.value = cartItems.value.filter(
                (ci) =>
                    !(
                        getCartLineKey(ci) === (quantityData.line_key || quantityData.id) ||
                        (ci.id === quantityData.id &&
                            Number(ci.variant_id || 0) === Number(quantityData.variant_id || 0) &&
                            Number(ci.modifier_id || 0) === Number(quantityData.modifier_id || 0))
                    )
            );
            saveCartToStorage(cartItems.value);
        }
    }
};

const handleRemoveItem = (itemId) => {
    const target = cartItems.value.find((ci) => getCartLineKey(ci) === itemId);
    const instanceKey = target?.combo_instance_key;

    if (instanceKey) {
        cartItems.value = cartItems.value.filter((ci) => ci.combo_instance_key !== instanceKey);
    } else if (target?.combo_pack_id) {
        // Legacy parity: persisted combos may not have combo_instance_key,
        // so remove the whole combo pack group by pack id.
        const packId = Number(target.combo_pack_id);
        cartItems.value = cartItems.value.filter((ci) => Number(ci.combo_pack_id || 0) !== packId);
    } else {
        cartItems.value = cartItems.value.filter((ci) => getCartLineKey(ci) !== itemId);
    }
    saveCartToStorage(cartItems.value);
};

/**
 * Remove a KOT item from a linked (persisted) order.
 * Calls DELETE /api/pos/orders/{orderId}/kot-items/{kotItemId}
 * with a mandatory reason that gets logged to kot_item_adjustments.
 */
const handleRemoveKotItem = async ({ kotItemId, reason }) => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId || !kotItemId) return;

    try {
        const response = await axios.delete(
            `/api/pos/orders/${activeOrderId}/kot-items/${kotItemId}`,
            { data: { reason } }
        );

        if (response.data?.data?.order_cancelled_or_deleted) {
            showPosAlert("success", "KOT item removed. Order has been cancelled.");
            // Redirect back to POS home
            window.location.href = "/pos";
            return;
        }

        showPosAlert("success", response.data?.message || "KOT item removed successfully.");

        // Reload the order to reflect updated totals and KOT state
        await loadOrderData(activeOrderId);
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.errors?.reason?.[0] || "Failed to remove KOT item.";
        showPosAlert("error", msg);
    }
};

/**
 * Remove an entire combo group from a linked KOT (legacy Pos::removeComboGroup
 * parity). Deletes each member kot_item sequentially with the SAME reason, then
 * reloads the order once at the end to reflect recomputed totals. If any
 * intermediate delete cancels the order (no items remain), we redirect home.
 */
const handleRemoveKotComboGroup = async ({ kotItemIds, reason }) => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId || !Array.isArray(kotItemIds) || kotItemIds.length === 0) {
        return;
    }

    try {
        for (const kotItemId of kotItemIds) {
            const response = await axios.delete(
                `/api/pos/orders/${activeOrderId}/kot-items/${kotItemId}`,
                { data: { reason } }
            );

            if (response.data?.data?.order_cancelled_or_deleted) {
                showPosAlert("success", "Combo removed. Order has been cancelled.");
                window.location.href = "/pos";
                return;
            }
        }

        showPosAlert("success", "Combo pack removed successfully.");
        await loadOrderData(activeOrderId);
    } catch (error) {
        const msg = error.response?.data?.message
            || error.response?.data?.errors?.reason?.[0]
            || "Failed to remove combo pack.";
        showPosAlert("error", msg);
        // Reflect whatever partial state the server applied before the failure.
        await loadOrderData(activeOrderId);
    }
};

/**
 * Reduce a KOT item quantity (decrement) on a linked order.
 * Calls PATCH /api/pos/orders/{orderId}/kot-items/{kotItemId}/quantity
 * with new_quantity and a mandatory reason that gets logged to kot_item_adjustments.
 */
const handleReduceKotItem = async ({ kotItemId, newQuantity, reason }) => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId || !kotItemId) return;

    try {
        const response = await axios.patch(
            `/api/pos/orders/${activeOrderId}/kot-items/${kotItemId}/quantity`,
            { new_quantity: newQuantity, reason }
        );

        if (response.data?.data?.order_cancelled_or_deleted) {
            showPosAlert("success", "KOT item updated. Order has been cancelled.");
            window.location.href = "/pos";
            return;
        }

        showPosAlert("success", response.data?.message || "KOT item updated.");
        await loadOrderData(activeOrderId);
    } catch (error) {
        const msg = error.response?.data?.message
            || error.response?.data?.errors?.reason?.[0]
            || error.response?.data?.errors?.new_quantity?.[0]
            || "Failed to update KOT item.";
        showPosAlert("error", msg);
    }
};

const handleApplyDiscount = (discountData) => {
    discountType.value = discountData.type;
    discountValue.value = discountData.value;
    calculateDiscountAmount();
};

const calculateDiscountAmount = () => {
    if (discountType.value === "fixed") {
        // Fixed amount discount
        discountAmount.value = discountValue.value;
    } else if (discountType.value === "percent") {
        // Percentage discount - calculate from subtotal
        const subTotal = cartItems.value.reduce(
            (sum, item) => sum + (item.price || 0) * (item.quantity || 1),
            0
        );
        discountAmount.value = (subTotal * discountValue.value) / 100;
    }
};

// Calculate total tax amount
const totalTaxAmount = computed(() => {
    return taxes.value.reduce((sum, tax) => sum + (tax.amount || 0), 0);
});

// Calculate taxes based on subtotal (after discount)
const calculateTaxes = () => {
    if (availableTaxes.value.length === 0) {
        taxes.value = [];
        return;
    }

    // Calculate subtotal from cart items
    const subTotal = cartItems.value.reduce(
        (sum, item) => sum + (item.price || 0) * (item.quantity || 1),
        0
    );

    // Calculate subtotal after discount
    const subTotalAfterDiscount = subTotal - discountAmount.value;

    // Calculate tax amounts for each tax
    const calculatedTaxes = availableTaxes.value.map((tax) => {
        const taxPercent = parseFloat(tax.tax_percent) || 0;
        const taxAmount = (subTotalAfterDiscount * taxPercent) / 100;

        return {
            id: tax.id,
            name: tax.tax_name || tax.name,
            tax_name: tax.tax_name || tax.name,
            rate: taxPercent,
            tax_percent: taxPercent,
            amount: taxAmount,
        };
    });

    taxes.value = calculatedTaxes;
};

// Recalculate percentage discount when cart items change
watch(
    cartItems,
    () => {
        if (discountType.value === "percent" && discountValue.value > 0) {
            calculateDiscountAmount();
        }
        calculateTaxes();
    },
    { deep: true }
);

// Recalculate taxes when discount changes
watch(
    [discountAmount, availableTaxes],
    () => {
        calculateTaxes();
    },
    { deep: true }
);

const handleRemoveDiscount = () => {
    discountAmount.value = 0;
    discountType.value = "";
    discountValue.value = 0;
};

// Reward Points handlers
const handleApplyRewardRedemption = (points) => {
    // Calculate discount locally — the actual redemption transaction
    // happens server-side when the order is billed (PosVueOrderController::store).
    const amountPerPoint = rewardAmountPerPoint.value || 1;
    const discount = Math.round(points * amountPerPoint * 100) / 100;

    rewardPointsRedeemed.value = points;
    rewardPointDiscount.value = discount;
};

const handleRemoveRewardRedemption = () => {
    rewardPointsRedeemed.value = 0;
    rewardPointDiscount.value = 0;
};

/** Reset Vue POS reward refs to defaults (new order / customer change / cart clear). */
const resetRewardState = () => {
    rewardPointDiscount.value = 0;
    rewardPointsRedeemed.value = 0;
    rewardPointsEarned.value = 0;
    rewardPointsAvailable.value = 0;
    rewardDisplayName.value = "Reward";
    rewardSettingsEnabled.value = false;
    rewardMaxRedeemable.value = 0;
    rewardAmountPerPoint.value = 1;
    canRedeemRewardPoints.value = false;
};

/**
 * Fetch reward points balance from the API when a customer is selected.
 * Also loads bootstrap-level reward settings for display_name, conversion rate, etc.
 */
const syncRewardState = async (customerIdOverride = null) => {
    const cid = customerIdOverride || customerId.value || customer.value?.id;

    // Load settings from bootstrap (cached, no API call needed)
    const bootstrapReward = bootstrapData.value?.reward_settings;
    if (!bootstrapReward || !bootstrapReward.enabled) {
        rewardSettingsEnabled.value = false;
        rewardPointsAvailable.value = 0;
        rewardMaxRedeemable.value = 0;
        canRedeemRewardPoints.value = false;
        return;
    }

    rewardSettingsEnabled.value = true;
    rewardDisplayName.value = bootstrapReward.display_name || "Reward";
    rewardAmountPerPoint.value = Number(bootstrapReward.redeem_amount_per_unit_point || 1);

    if (!cid) {
        rewardPointsAvailable.value = 0;
        rewardMaxRedeemable.value = 0;
        canRedeemRewardPoints.value = false;
        return;
    }

    try {
        const currentSubTotal = cartItems.value.reduce(
            (sum, item) => sum + Number(item.price || 0) * Number(item.quantity || 1),
            0
        );
        const { data } = await axios.get("/api/pos/customer-reward-balance", {
            params: {
                customer_id: cid,
                order_subtotal: currentSubTotal,
            },
        });

        if (data?.success && data?.data) {
            const d = data.data;
            rewardPointsAvailable.value = d.available_points || 0;
            rewardMaxRedeemable.value = d.max_redeemable || 0;
            rewardAmountPerPoint.value = d.amount_per_point || 1;
            rewardDisplayName.value = d.display_name || "Reward";
            canRedeemRewardPoints.value = !!d.can_redeem;
        }
    } catch (error) {
        console.error("Error fetching reward balance:", error);
        rewardPointsAvailable.value = 0;
        rewardMaxRedeemable.value = 0;
        canRedeemRewardPoints.value = false;
    }
};

let rewardBalanceDebounce = null;
watch(
    cartItems,
    () => {
        if (rewardBalanceDebounce) {
            clearTimeout(rewardBalanceDebounce);
        }
        rewardBalanceDebounce = setTimeout(() => {
            syncRewardState();
        }, 320);
    },
    { deep: true }
);

// Save order number to localStorage
const saveOrderNumberToStorage = (orderNum) => {
    try {
        if (orderNum) {
            localStorage.setItem("pos_last_order_number", orderNum);
            console.log("Saved order number to localStorage:", orderNum);
        }
    } catch (error) {
        console.error("Error saving order number to localStorage:", error);
    }
};

// Load last order number from localStorage
const loadOrderNumberFromStorage = () => {
    try {
        const saved = localStorage.getItem("pos_last_order_number");
        return saved || null;
    } catch (error) {
        console.error("Error loading order number from localStorage:", error);
        return null;
    }
};

// Extract last numeric value and its format from formatted order number
const extractLastNumericSegment = (formattedNumber) => {
    if (!formattedNumber) return null;

    // Remove any existing "(Offline)" suffix for processing
    const cleanNumber = formattedNumber.replace(/\s*\(Offline\)\s*$/, "");

    // Find the last sequence of digits (could be padded like 001, 023, etc.)
    // This regex finds the last occurrence of one or more digits
    const matches = cleanNumber.match(/(\d+)(?=[^\d]*$)/);

    if (matches && matches.length > 0) {
        const lastDigits = matches[1];
        const numericValue = parseInt(lastDigits, 10);
        const digitLength = lastDigits.length;
        const prefix = cleanNumber.substring(0, matches.index);
        const suffix = cleanNumber.substring(matches.index + lastDigits.length);

        return {
            numericValue,
            digitLength,
            prefix,
            suffix,
            fullNumber: cleanNumber,
        };
    }

    return null;
};

// Increment order number offline with lock to prevent concurrent mutations
const incrementOrderNumberOffline = async () => {
    const LOCK_KEY = "orderNumberLock";
    const LOCK_TIMEOUT = 3000; // 3 second lock timeout
    const MAX_RETRIES = 5;
    let retries = 0;

    while (retries < MAX_RETRIES) {
        try {
            // Try to acquire lock
            const lockData = localStorage.getItem(LOCK_KEY);
            if (lockData) {
                const { expiry, owner } = JSON.parse(lockData);
                // If lock exists and not expired, wait and retry
                if (Date.now() < expiry) {
                    retries++;
                    await new Promise(resolve => setTimeout(resolve, Math.pow(2, retries) * 100)); // Backoff
                    continue;
                }
            }

            // Acquire the lock
            const lock = {
                owner: tabId.value,
                expiry: Date.now() + LOCK_TIMEOUT
            };
            localStorage.setItem(LOCK_KEY, JSON.stringify(lock));

            // Now proceed with increment
            const lastOrderNumber = loadOrderNumberFromStorage();
            if (!lastOrderNumber) {
                // If no last order number, use a default
                orderNumber.value = "Order #001";
                saveOrderNumberToStorage(orderNumber.value);
            } else {
                const extracted = extractLastNumericSegment(lastOrderNumber);
                if (extracted) {
                    const newNumericValue = extracted.numericValue + 1;
                    // Preserve the digit length (padding) from the original
                    const paddedNewValue = String(newNumericValue).padStart(
                        extracted.digitLength,
                        "0"
                    );

                    // Reconstruct the order number with incremented value
                    orderNumber.value = `${extracted.prefix}${paddedNewValue}${extracted.suffix}`;
                    saveOrderNumberToStorage(orderNumber.value);
                    console.log("Incremented order number offline:", orderNumber.value);
                } else {
                    // If we can't extract a number, try to append digits
                    // Remove "(Offline)" if present
                    const cleanNumber = lastOrderNumber.replace(/\s*\(Offline\)\s*$/, "");
                    orderNumber.value = `${cleanNumber}-001`;
                    saveOrderNumberToStorage(orderNumber.value);
                    console.log(
                        "Could not extract number, using fallback:",
                        orderNumber.value
                    );
                }
            }

            // Release the lock
            localStorage.removeItem(LOCK_KEY);
            break; // Success, exit loop
        } catch (error) {
            console.error("Error incrementing order number:", error);
            // Try to release lock if it's ours
            try {
                const lockData = localStorage.getItem(LOCK_KEY);
                if (lockData) {
                    const { owner } = JSON.parse(lockData);
                    if (owner === tabId.value) {
                        localStorage.removeItem(LOCK_KEY);
                    }
                }
            } catch (e) {
                // Ignore lock cleanup errors
            }
            break;
        }
    }
};

// Fetch new order number from API
const fetchNewOrderNumber = async () => {
    try {
        const response = await axios.get("/api/pos/get-order-number");
        // API returns array format: [order_number, formatted_order_number]
        if (Array.isArray(response.data) && response.data.length >= 2) {
            orderNumber.value = response.data[1] || response.data[0] || "";
        } else if (response.data?.formatted_order_number) {
            orderNumber.value = response.data.formatted_order_number;
        } else if (response.data?.order_number) {
            orderNumber.value = response.data.order_number;
        }

        // Save to localStorage when online
        if (orderNumber.value) {
            saveOrderNumberToStorage(orderNumber.value);
        }

        console.log("Fetched new order number:", orderNumber.value);
    } catch (error) {
        console.error("Error fetching order number:", error);
        orderNumber.value = "";
    }
};

const getOrder = async () => {
    try {
        const response = await axios.get(`/api/pos/orders/${orderId.value}`);
        order.value = response.data;
        console.log("Order fetched:", order.value);
    } catch (error) {
        console.error("Error fetching order:", error);
        order.value = null;
    }
};

const dispatchLivewireEvent = (eventName, payload) => {
    if (typeof window === "undefined") {
        return false;
    }

    const livewire = window.Livewire;
    if (!livewire || typeof livewire.dispatch !== "function") {
        return false;
    }

    livewire.dispatch(eventName, payload);
    return true;
};

const navigateToLinkedOrderDetail = (id) => {
    if (!id) {
        return;
    }

    window.location.href = `/pos/kot/${id}?show-order-detail=true`;
};

const navigateToPayment = (id) => {
    if (!id) {
        return;
    }

    window.location.href = `/orders/${id}?payment=true`;
};

const openPaymentInPlace = (id) => {
    if (!id) {
        return false;
    }

    return dispatchLivewireEvent("showPaymentModal", {
        id,
    });
};

const openOrderDetailInPlace = (id) => {
    if (!id) {
        return false;
    }

    return dispatchLivewireEvent("showOrderDetail", {
        id,
        fromPos: true,
    });
};

const openBillPrintWindow = (id) => {
    if (!id) {
        return;
    }

    const url = `/orders/print/${id}`;
    const printWindow = window.open(url, "_blank");

    if (printWindow) {
        setTimeout(() => {
            printWindow.print();
        }, 1000);
    }
};

const openKotPrintWindows = (urls = []) => {
    const printUrls = Array.isArray(urls)
        ? urls.filter(Boolean)
        : [];

    if (printUrls.length === 0) {
        return;
    }

    printUrls.forEach((url, index) => {
        setTimeout(() => {
            const printWindow = window.open(url, "_blank");

            if (printWindow) {
                setTimeout(() => {
                    printWindow.print();
                }, 1000);
            }
        }, index * 650);
    });
};

const clearCartAfterSave = () => {
    // Clear cart after successful order save (Speeder behavior)
    // This ensures fresh start for next order
    cartItems.value = [];
    saveCartToStorage([]);

    // Clear selected customer like other order draft state
    customer.value = getEmptyCustomer();
    customerId.value = null;
    clearCustomerFromStorage(); // Properly remove from localStorage

    orderNote.value = "";
    discountAmount.value = 0;
    discountType.value = "";
    discountValue.value = 0;
    extraCharges.value = [];
    customExtras.value = [];
    availableTaxes.value = [];
    orderId.value = null;
    orderStatus.value = "";
    orderLifecycleStatus.value = "";
    selectedDeliveryExecutive.value = "";
    deliveryFee.value = 0;
    orderPermissions.value = {
        can_update_order: false,
        can_delete_order: false,
        can_edit_billed_order: false,
        can_delete_kot_item: false,
    };
    kotGroups.value = [];
    deliveryAddress.value = "";
    customerPhone.value = "";
    customerLat.value = null;
    customerLng.value = null;
    currentTable.value = "";
    currentTableId.value = null;
    calculateTaxes();
    resetRewardState();
};

const handleSaveOrder = async (...actions) => {
    // Create action key for tracking which button is being pressed
    const actionKey = actions.join("_") || "kot"; // e.g., "kot", "bill", "kot_print", "bill_payment", etc.
    savingAction.value = actionKey;
    try {
        // Validate cart has items
        // In linked-order mode, existing items are on the server — cart may be empty if no NEW items are added
        if (!isLinkedOrderMode.value && (!cartItems.value || cartItems.value.length === 0)) {
            showPosAlert("error", "Cart is empty. Please add items before saving.");
            savingAction.value = null;
            return;
        }

        const actionList = Array.isArray(actions) ? actions : [];
        const routeLinkedOrderId = params.orderId ? Number(params.orderId) : null;
        const effectiveOrderId = orderId.value
            ? Number(orderId.value)
            : routeLinkedOrderId;
        const isExistingOrder = !!effectiveOrderId;
        const action = actionList.includes("bill") ? "bill" : "kot";
        const secondaryAction = actionList.includes("payment")
            ? "payment"
            : actionList.includes("print")
                ? "print"
                : null;
        const openPayment = secondaryAction === "payment";
        const selectedOrderType = resolveOrderType(orderType.value);

        console.log("[POS DEBUG] saveOrder start", {
            actionList,
            isExistingOrder,
            effectiveOrderId,
            action,
            secondaryAction,
            openPayment,
            currentOrderId: orderId.value,
            routeLinkedOrderId,
            isLinkedOrderMode: isLinkedOrderMode.value,
        });

        // Calculate line totals with proper amount calculation
        const lines = cartItems.value.map((item) => {
            const unitPrice = Number(item.price || 0);
            const quantity = Number(item.quantity || 1);
            const amount = unitPrice * quantity; // qty × price

            return {
                menu_item_id: Number(item.menu_item_id || item.id),
                menu_item_variation_id: item.variant_id
                    ? Number(item.variant_id)
                    : null,
                qty: quantity,
                amount: amount, // Include calculated amount
                unit_price: unitPrice, // Include unit price for backend validation
                note: item.note || null,
                modifier_option_quantities:
                    item.modifier_option_quantities || {},
                combo_pack_id: item.combo_pack_id || null,
                combo_instance_key: item.combo_instance_key || null,
            };
        });

        const orderData = {
            order_id: effectiveOrderId || null,
            action,
            open_payment: openPayment,
            secondary_action: secondaryAction,
            order_type_id: selectedOrderType?.id || null,
            delivery_app_id:
                normalizeOrderTypeSlug(selectedOrderType?.slug) === "delivery"
                    ? selectedDeliveryApp.value || "default"
                    : null,
            // Legacy parity (Pos.php::saveOrder line 2863/2913): persist table_id when
            // a table is assigned in dine_in mode so POS orders can be linked to tables
            // and table status is managed correctly. For delivery/pickup, table_id is null.
            table_id: normalizeOrderTypeSlug(selectedOrderType?.slug || orderType.value) === "dine_in"
                ? currentTableId.value || null
                : null,
            waiter_id: waiterId.value,
            note: orderNote.value,
            customer_id: customerId.value || customer.value?.id || null,
            customer: customer.value?.id
                ? {
                    id: customer.value.id,
                    name: customer.value.name || "",
                    phone: customer.value.phone || "",
                    phone_code: customer.value.phone_code || "",
                    email: customer.value.email || null,
                    address: customer.value.address || null,
                }
                : null,
            delivery_executive_id:
                normalizeOrderTypeSlug(selectedOrderType?.slug || orderType.value) === "delivery"
                    ? (selectedDeliveryExecutive.value ? Number(selectedDeliveryExecutive.value) : null)
                    : null,
            delivery_fee:
                normalizeOrderTypeSlug(selectedOrderType?.slug || orderType.value) === "delivery"
                    ? Number(deliveryFee.value || 0)
                    : 0,
            lines: lines,
            // Legacy parity (Pos.php::$appendOnlyKotSave): New KOT screen posts
            // only the new delta lines; the server must preserve existing
            // items/KOTs/taxes instead of wiping and recreating the order.
            // Applies to both KOT and "KOT + Bill + Payment" flows from the
            // New KOT screen — the existing history is always preserved.
            append_kot: isNewKotMode.value,
            // Legacy parity (Pos.php::syncOrderExtras): send current rows as-is
            // when enabled so the server can replace order_extras for the order.
            // In New KOT append mode the server intentionally ignores this and
            // keeps existing extras, matching legacy behavior.
            custom_extras: allowCustomOrderExtras.value
                ? customExtras.value.map((row) => ({
                    amount: Number(row?.amount || 0),
                    note: String(row?.note || ""),
                }))
                : [],
            // Reward points redemption — sent to server for persistence;
            // actual balance deduction happens at billing time in PosVueOrderController::store.
            reward_points_redeemed: rewardPointsRedeemed.value > 0 ? rewardPointsRedeemed.value : null,
            reward_point_discount: rewardPointDiscount.value > 0 ? rewardPointDiscount.value : null,
        };

        console.log("Order data being sent:", {
            orderData: orderData,
            selectedOrderType: selectedOrderType,
            orderType: orderType.value,
            waiterId: waiterId.value,
            cartItems: cartItems.value,
        });

        // Use offline API call wrapper
        const result = await offlineApiCall(
            async () => {
                // API call when online
                const response = await axios.post("/api/pos/orders", orderData);
                return response.data;
            },
            {
                type: "save_order",
                data: orderData,
            }
        );

        if (result.offline) {
            console.log("Order queued for sync:", result.operationId);

            if (isExistingOrder) {
                // Existing linked orders should preserve current cart/view state.
                showPosAlert("info", "Order update queued and will sync when online.");
            } else {
                // Clear draft state after queueing for sync (new-order flow)
                clearCartAfterSave();

                // Increment order number for next offline order
                await incrementOrderNumberOffline();
            }
        } else {
            console.log("Order saved successfully:", result.data);

            const resultPayload = result?.data?.data ?? result?.data ?? {};
            const orderIdToOpen = resultPayload.order_id
                ? Number(resultPayload.order_id)
                : null;
            const resolvedOrderId = orderIdToOpen || effectiveOrderId || null;
            const nextAction = resultPayload.next || {};
            const shouldOpenPayment = Boolean(
                nextAction.open_payment ?? openPayment
            );
            const shouldPrintReceipt = Boolean(
                nextAction.print_receipt ?? actionList.includes("print")
            );
            const shouldShowOrderDetail = Boolean(
                nextAction.show_order_detail ?? (actionList.includes("bill") && !shouldOpenPayment && !shouldPrintReceipt)
            );

            console.log("[POS DEBUG] saveOrder decoded response", {
                rawResult: result?.data,
                resultPayload,
                orderIdToOpen,
                resolvedOrderId,
                links: resultPayload.links || null,
                nextAction,
                shouldOpenPayment,
                shouldPrintReceipt,
                shouldShowOrderDetail,
            });

            if (isExistingOrder && resolvedOrderId) {
                orderId.value = String(resolvedOrderId);

                // Keep linked footer state in sync immediately after billing.
                if (actionList.includes("bill")) {
                    orderLifecycleStatus.value = "billed";
                    showOrderDetailMode.value = true;
                    mode.value = "kot";
                }
            }

            if (isExistingOrder && resolvedOrderId) {
                // Legacy parity (Pos.php line 3395): after a successful KOT save on an
                // existing order (New KOT flow from /pos/kot/{id}), legacy navigates to
                // the linked order detail view. Mirror that so the user lands on the
                // freshly appended KOT instead of an empty "New KOT" screen. KOT+print
                // fires the kitchen print windows first, then redirects.
                if (isNewKotMode.value && action === "kot" && !shouldOpenPayment) {
                    if (actionList.includes("print")) {
                        const kotPrintUrls = resultPayload.links?.kot_print_urls || [];
                        openKotPrintWindows(kotPrintUrls);
                    }
                    navigateToLinkedOrderDetail(resolvedOrderId);
                    return;
                }

                if (shouldOpenPayment) {
                    console.log("[POS DEBUG] existing order -> payment", { resolvedOrderId });
                    const openedPayment = openPaymentInPlace(resolvedOrderId);

                    if (!openedPayment) {
                        navigateToPayment(resolvedOrderId);
                    } else {
                        // Keep linked cart/footer in sync with billed status after in-place modal open.
                        await loadOrderData(resolvedOrderId);
                    }
                    return;
                }

                if (shouldPrintReceipt && actionList.includes("bill")) {
                    console.log("[POS DEBUG] existing order -> bill print", { resolvedOrderId });
                    openBillPrintWindow(resolvedOrderId);

                    if (shouldShowOrderDetail) {
                        navigateToLinkedOrderDetail(resolvedOrderId);
                        return;
                    }

                    await loadOrderData(resolvedOrderId);
                    return;
                }

                if (shouldShowOrderDetail) {
                    console.log("[POS DEBUG] existing order -> bill detail", { resolvedOrderId });

                    const openedOrderDetail = openOrderDetailInPlace(resolvedOrderId);
                    if (!openedOrderDetail) {
                        navigateToLinkedOrderDetail(resolvedOrderId);
                    } else {
                        // Legacy immediately reflects billed footer state; mirror that by rehydrating.
                        await loadOrderData(resolvedOrderId);
                    }
                    return;
                }

                // Preserve linked order context and refresh in-place for non-navigating actions.
                await loadOrderData(resolvedOrderId);
                return;
            }

            if (isExistingOrder) {
                console.warn("[POS DEBUG] existing order guard prevented clearCartAfterSave", {
                    effectiveOrderId,
                    orderIdToOpen,
                    resultPayload,
                });

                if (effectiveOrderId) {
                    await loadOrderData(effectiveOrderId);
                }
                return;
            }

            // Clear cart after successful save (new-order flow)
            clearCartAfterSave();

            if (shouldOpenPayment && orderIdToOpen) {
                console.log("[POS DEBUG] new order -> bill payment", { orderIdToOpen });
                const openedPayment = openPaymentInPlace(orderIdToOpen);

                if (!openedPayment) {
                    window.location.href = `/orders/${orderIdToOpen}?payment=true`;
                }
            } else if (shouldShowOrderDetail && orderIdToOpen) {
                console.log("[POS DEBUG] new order -> bill detail", { orderIdToOpen });
                const opened = openOrderDetailInPlace(orderIdToOpen);

                if (!opened) {
                    window.location.href = `/orders/${orderIdToOpen}`;
                }
            }

            // Handle print action
            if (shouldPrintReceipt) {
                const kotPrintUrls = resultPayload.links?.kot_print_urls || [];
                const printUrl = actionList.includes("bill")
                    ? (resultPayload.links?.bill || `/orders/print/${orderIdToOpen}`)
                    : kotPrintUrls[0] || resultPayload.links?.kot;

                console.log("[POS DEBUG] print decision", {
                    actionList,
                    orderIdToOpen,
                    printUrl,
                    kotPrintUrls,
                });

                if (actionList.includes("bill")) {
                    if (printUrl) {
                        setTimeout(() => {
                            const printWindow = window.open(printUrl, '_blank');
                            setTimeout(() => {
                                if (printWindow) {
                                    printWindow.print();
                                }
                            }, 1000);
                        }, 500);
                    } else {
                        console.warn("[POS DEBUG] Print URL not available in response", {
                            actionList,
                            orderIdToOpen,
                            resultPayload,
                        });
                    }
                } else if (kotPrintUrls.length > 0) {
                    openKotPrintWindows(kotPrintUrls);
                } else if (printUrl) {
                    // Open print window
                    setTimeout(() => {
                        const printWindow = window.open(printUrl, '_blank');
                        // Trigger print after a short delay to ensure document loads
                        setTimeout(() => {
                            if (printWindow) {
                                printWindow.print();
                                // Optionally close window after user finishes or clicks close
                                // printWindow.close();
                            }
                        }, 1000);
                    }, 500);
                } else {
                    console.warn("[POS DEBUG] Print URL not available in response", {
                        actionList,
                        orderIdToOpen,
                        resultPayload,
                    });
                }
            }

            if (!orderIdToOpen) {
                console.warn("[POS DEBUG] Missing orderIdToOpen after save", {
                    actionList,
                    rawResult: result?.data,
                });
            }

            // Fetch new order number when online
            if (isOnline.value) {
                await fetchNewOrderNumber();
            } else {
                // If somehow we're offline but order was saved, increment offline
                await incrementOrderNumberOffline();
            }
        }
    } catch (error) {
        const errorMessage = error?.response?.data?.message || error?.message || "Failed to save order";
        const errors = error?.response?.data?.errors || {};
        console.error("Error saving order:", {
            message: errorMessage,
            errors: errors,
            status: error?.response?.status,
            data: error?.response?.data,
            fullError: error
        });

        // Legacy alert style: keep the message simple and direct
        if (Object.keys(errors).length > 0) {
            const firstError = Object.values(errors)
                .flat()
                .filter(Boolean)
                .map((message) => String(message))
                .join("\n");
            showPosAlert("error", firstError || errorMessage);
        } else {
            showPosAlert("error", errorMessage);
        }
    } finally {
        savingAction.value = null;
    }
};

const handleConfirmSameCustomer = () => {
    // TODO: Implement API call to confirm same customer
    console.log("confirmSameCustomer");
    showReservationModal.value = false;
};

const handleConfirmDifferentCustomer = () => {
    // TODO: Implement API call to confirm different customer
    console.log("confirmDifferentCustomer");
    showReservationModal.value = false;
};

const handleConfirmTableChange = async () => {
    await applySelectedTable(
        newTable.value,
        newTableId.value,
        newTableActiveOrderId.value
    );
    newTableActiveOrderId.value = null;
    showTableChangeConfirmationModal.value = false;
};

const resolveActiveOrderId = () => {
    const rawId = orderId.value || params.orderId || order.value || null;
    const numericId = Number(rawId);
    return Number.isFinite(numericId) && numericId > 0 ? numericId : null;
};

const applyCustomerState = (customerData) => {
    const nextCustomer = normalizeCustomerPayload(customerData);
    customer.value = nextCustomer;
    customerId.value = nextCustomer?.id || null;
    customerPhone.value = nextCustomer?.phone
        ? `${nextCustomer.phone_code ? `+${nextCustomer.phone_code} ` : ""}${nextCustomer.phone}`
        : "";
    deliveryAddress.value = nextCustomer?.delivery_address || nextCustomer?.address || "";
};

const handleSaveCustomer = async (customerData) => {
    // Customer is already created/updated in AddCustomerModal; for linked
    // orders, immediately attach it to the order so no KOT/Bill click is needed.
    const activeOrderId = resolveActiveOrderId();
    const previousCustomer = { ...customer.value };
    const previousCustomerId = customerId.value;
    const previousPhone = customerPhone.value;
    const previousAddress = deliveryAddress.value;

    applyCustomerState(customerData);
    resetRewardState();

    try {
        if (activeOrderId) {
            await axios.post(`/api/pos/orders/${activeOrderId}/customer`, {
                customer_id: customerData?.id || null,
            });
        }

        clearCustomerFromStorage();
        showAddCustomerModal.value = false;

        // Sync reward points for the newly attached customer
        syncRewardState(customerData?.id);
    } catch (error) {
        customer.value = previousCustomer;
        customerId.value = previousCustomerId;
        customerPhone.value = previousPhone;
        deliveryAddress.value = previousAddress;
        syncRewardState(previousCustomerId);

        const message = error?.response?.data?.message || "Failed to update customer on order.";
        console.error("Error updating customer on order:", error);
        showPosAlert("error", message);
    }
};

const handleRemoveCustomer = async () => {
    const previousCustomer = { ...customer.value };
    const previousCustomerId = customerId.value;
    const previousPhone = customerPhone.value;
    const previousAddress = deliveryAddress.value;

    customer.value = getEmptyCustomer();
    customerId.value = null;
    customerPhone.value = "";
    deliveryAddress.value = "";
    clearCustomerFromStorage();

    resetRewardState();

    // Sync removal to backend if editing an existing order
    const activeOrderId = resolveActiveOrderId();
    if (activeOrderId) {
        try {
            await axios.post(`/api/pos/orders/${activeOrderId}/customer`, {
                customer_id: null,
            });
        } catch (error) {
            customer.value = previousCustomer;
            customerId.value = previousCustomerId;
            customerPhone.value = previousPhone;
            deliveryAddress.value = previousAddress;

            const message = error?.response?.data?.message || "Failed to remove customer from order.";
            console.error("Error removing customer from order:", error);
            showPosAlert("error", message);
        }
    }
};

const handleWaiterUpdate = async (newWaiterId) => {
    waiterId.value = newWaiterId ? Number(newWaiterId) : "";

    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId) {
        return;
    }

    try {
        const response = await axios.post(`/api/pos/orders/${activeOrderId}/waiter`, {
            waiter_id: waiterId.value || null,
        });

        if (response.data?.success) {
            showPosAlert("success", response.data?.message || "Waiter updated successfully");
        }
    } catch (error) {
        console.error("Error updating waiter:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to update waiter");
    }
};

const handleOrderStatusUpdate = async (nextOrderStatus) => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId || !nextOrderStatus) {
        return;
    }

    if (String(nextOrderStatus).toLowerCase() === "cancelled") {
        const confirmed = await showPosConfirm(
            "Are you sure you want to cancel this order?",
            {
                confirmButtonText: "Yes, cancel",
                cancelButtonText: "No",
            }
        );

        if (!confirmed) {
            return;
        }
    }

    try {
        const response = await axios.post(`/api/pos/orders/${activeOrderId}/status`, {
            order_status: nextOrderStatus,
        });

        if (response.data?.success) {
            orderStatus.value = response.data?.data?.order_status || nextOrderStatus;
            showPosAlert("success", response.data?.message || "Order status updated successfully");
        }
    } catch (error) {
        console.error("Error updating order status:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to update order status");
    }
};

const handleDeliveryExecutiveUpdate = async (newDeliveryExecutiveId) => {
    selectedDeliveryExecutive.value = newDeliveryExecutiveId
        ? Number(newDeliveryExecutiveId)
        : "";

    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId) {
        return;
    }

    try {
        const response = await axios.post(
            `/api/pos/orders/${activeOrderId}/delivery-executive`,
            {
                delivery_executive_id: selectedDeliveryExecutive.value || null,
            }
        );

        if (response.data?.success) {
            showPosAlert("success", response.data?.message || "Delivery executive updated successfully");
        }
    } catch (error) {
        console.error("Error updating delivery executive:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to update delivery executive");
    }
};

const handleDeliveryFeeUpdate = async (newDeliveryFee) => {
    const normalizedDeliveryFee = Number(newDeliveryFee || 0);
    deliveryFee.value = normalizedDeliveryFee < 0 ? 0 : normalizedDeliveryFee;

    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId) {
        return;
    }

    try {
        const response = await axios.post(`/api/pos/orders/${activeOrderId}/delivery-fee`, {
            delivery_fee: deliveryFee.value,
        });

        if (response.data?.success) {
            showPosAlert("success", response.data?.message || "Delivery fee updated successfully");
        }
    } catch (error) {
        console.error("Error updating delivery fee:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to update delivery fee");
    }
};

const handleOpenPayment = () => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    console.log("[POS DEBUG] open payment clicked", {
        activeOrderId,
        isLinkedOrderMode: isLinkedOrderMode.value,
    });

    if (!activeOrderId) {
        console.warn("[POS DEBUG] open payment skipped: missing order id");
        return;
    }

    if (isLinkedOrderMode.value) {
        const openedPayment = openPaymentInPlace(activeOrderId);
        if (!openedPayment) {
            navigateToPayment(activeOrderId);
        }
        return;
    }

    const openedPayment = openPaymentInPlace(activeOrderId);

    console.log("[POS DEBUG] open payment dispatch result", {
        activeOrderId,
        openedPayment,
    });

    if (!openedPayment) {
        window.location.href = `/orders/${activeOrderId}?payment=true`;
    }
};

// Mirrors legacy order_detail.blade.php → printOrder({id}) on `paid` orders.
const handlePrintReceipt = () => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (!activeOrderId) {
        console.warn("[POS DEBUG] print receipt skipped: missing order id");
        return;
    }

    openBillPrintWindow(activeOrderId);
};

const handleNewKot = () => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    console.log("[POS DEBUG] new KOT clicked", {
        activeOrderId,
        isLinkedOrderMode: isLinkedOrderMode.value,
        orderLifecycleStatus: orderLifecycleStatus.value,
    });

    if (!activeOrderId) {
        console.warn("[POS DEBUG] new KOT skipped: missing order id");
        return;
    }

    window.location.href = `/pos/kot/${activeOrderId}`;
};

const handleDeleteOrder = async () => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    console.log("[POS DEBUG] delete order clicked", {
        activeOrderId,
        isLinkedOrderMode: isLinkedOrderMode.value,
        orderLifecycleStatus: orderLifecycleStatus.value,
    });

    if (!activeOrderId) {
        console.warn("[POS DEBUG] delete order skipped: missing order id");
        return;
    }

    const confirmed = await showPosConfirm(
        "Are you sure you want to delete this order?",
        {
            confirmButtonText: "Yes, delete",
            cancelButtonText: "No",
        }
    );

    if (!confirmed) {
        console.log("[POS DEBUG] delete order cancelled by user", {
            activeOrderId,
        });
        return;
    }

    try {
        const response = await axios.delete(`/api/pos/orders/${activeOrderId}`);

        if (response.data?.success) {
            console.log("[POS DEBUG] delete order success", {
                activeOrderId,
            });
            showPosAlert("success", response.data?.message || "Order deleted successfully");
            clearCartAfterSave();
            window.location.href = "/pos";
        }
    } catch (error) {
        console.error("[POS DEBUG] delete order failed", {
            activeOrderId,
            status: error?.response?.status,
            data: error?.response?.data,
        });
        console.error("Error deleting order:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to delete order");
    }
};

// Legacy parity (Pos.php::addOrderExtraRow / removeOrderExtraRow / normalizeOrderExtras):
// add an empty row, remove by index, and keep numeric amounts coerced on blur.
const handleAddCustomExtra = () => {
    customExtras.value.push({ amount: "", note: "" });
};

const handleRemoveCustomExtra = (index) => {
    if (index < 0 || index >= customExtras.value.length) {
        return;
    }
    customExtras.value.splice(index, 1);
};

const handleUpdateCustomExtra = ({ index, field, value }) => {
    const row = customExtras.value[index];
    if (!row) {
        return;
    }

    if (field === "amount") {
        // Keep the raw string while the user is typing so partial decimal entries
        // (e.g. "2." before a trailing digit) aren't stomped. Coercion to a
        // non-negative number happens at save/compute time.
        row.amount = value;
        return;
    }

    if (field === "note") {
        row.note = String(value ?? "");
    }
};

const handleRemoveExtraCharge = async (chargeId) => {
    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    // Optimistically remove from local state
    extraCharges.value = extraCharges.value.filter((c) => Number(c.id) !== Number(chargeId));

    if (!activeOrderId) {
        return;
    }

    try {
        await axios.delete(`/api/pos/orders/${activeOrderId}/extra-charges/${chargeId}`);
    } catch (error) {
        console.error("Error removing extra charge:", error);
        showPosAlert("error", error.response?.data?.message || "Failed to remove extra charge");
        // Reload order data to restore correct state
        loadOrderData(activeOrderId);
    }
};

const handlePickupDateTimeUpdate = (value) => {
    pickupDateTime.value = value || "";
};

// Sync handler for pending operations
const syncHandler = async (operation) => {
    try {
        switch (operation.type) {
            case "save_order":
                const orderResponse = await axios.post(
                    "/api/pos/orders",
                    operation.data
                );
                console.log("Synced order:", orderResponse.data);
                return orderResponse.data;

            case "save_customer":
                const customerResponse = await axios.post(
                    "/api/pos/customers",
                    operation.data
                );
                console.log("Synced customer:", customerResponse.data);
                return customerResponse.data;

            default:
                console.warn("Unknown operation type:", operation.type);
        }
    } catch (error) {
        console.error("Error syncing operation:", error);
        throw error;
    }
};

// Watch for online status changes and sync when coming back online
watch(isOnline, (online) => {
    if (online && pendingOperations.value.length > 0) {
        console.log("Connection restored, syncing pending operations...");
        syncPendingOperations(syncHandler);
    }
});

// Load restaurant data
const loadRestaurantData = () => {
    const bootstrap = bootstrapData.value;
    if (bootstrap?.currency_symbol) {
        currencySymbol.value = bootstrap.currency_symbol;
    }

    if (bootstrap) {
        restaurant.value = {
            tax_mode: bootstrap.tax_mode || "item",
        };

        allowCustomOrderExtras.value = !!bootstrap.allow_custom_order_extras;

        // KOT module gate — check if 'KOT' is in the restaurant's active modules
        if (Array.isArray(bootstrap.modules)) {
            kotModuleEnabled.value = bootstrap.modules.includes('KOT');
        } else {
            kotModuleEnabled.value = true; // Default to enabled if modules not provided
        }
    }
};

// Load menu data from cache or API
// Load menu data from bootstrap snapshot (no API call, just reads from bootstrap)
const loadMenuData = () => {
    // Load from server-provided bootstrap snapshot
    const bootstrap = bootstrapData.value;
    console.log("=== Loading Menu Data ===");
    console.log("Bootstrap data available:", !!bootstrap);

    if (bootstrap) {
        if (Array.isArray(bootstrap.menus) && bootstrap.menus.length > 0) {
            menus.value = bootstrap.menus;
            console.log("Loaded menus:", menus.value.length);
        }

        if (
            Array.isArray(bootstrap.categories) &&
            bootstrap.categories.length > 0
        ) {
            categories.value = bootstrap.categories;
            console.log("Loaded categories:", categories.value.length);
        }

        if (Array.isArray(bootstrap.items) && bootstrap.items.length > 0) {
            menuItems.value = bootstrap.items;
            console.log("Loaded menu items:", menuItems.value.length);
            // Log first item to debug
            if (menuItems.value.length > 0) {
                console.log("First item:", menuItems.value[0]);
                console.log("Sample prices:", menuItems.value.slice(0, 3).map(i => ({ id: i.id, name: i.item_name, price: i.price, variations_count: i.variations_count })));
            }

            // Build flat modifier options map: { [optionId]: { name, price } }
            // Used by OrderPanel to render modifier pill labels in the cart.
            // Includes options from both base modifier_groups AND
            // variation-specific groups so variation-only modifiers don't
            // fall through to "Modifier #id" labels.
            const optionsMap = {};
            const harvestGroups = (groups) => {
                if (!Array.isArray(groups)) return;
                groups.forEach((group) => {
                    const options = Array.isArray(group.options) ? group.options : [];
                    options.forEach((opt) => {
                        optionsMap[opt.id] = { name: opt.name, price: opt.price ?? 0 };
                    });
                });
            };
            menuItems.value.forEach((item) => {
                harvestGroups(item.modifier_groups);
                const vmap = item.variation_modifier_groups || {};
                Object.keys(vmap).forEach((variationId) => {
                    harvestGroups(vmap[variationId]);
                });
            });
            modifierOptions.value = optionsMap;
        }

        if (
            Array.isArray(bootstrap.combo_packs) &&
            bootstrap.combo_packs.length > 0
        ) {
            comboPacks.value = bootstrap.combo_packs;
        }

        hideMenuItemImageOnPos.value = !!bootstrap.hide_menu_item_image_on_pos;

        if (Array.isArray(bootstrap.order_types) && bootstrap.order_types.length > 0) {
            orderTypes.value = bootstrap.order_types;
            console.log("Loaded order types:", orderTypes.value);
        }

        if (bootstrap.current_user) {
            currentUser.value = bootstrap.current_user;
            canEditWaiter.value = !!bootstrap.current_user.can_update_order;
        }

        if (bootstrap.pos_preferences) {
            const selectedApp = bootstrap.pos_preferences.selected_delivery_app;
            selectedDeliveryApp.value = selectedApp ? String(selectedApp) : "default";

            const preferredOrderTypeId = Number(bootstrap.pos_preferences.default_order_type_id || 0);
            defaultOrderTypeId.value = preferredOrderTypeId > 0 ? preferredOrderTypeId : null;
            if (!orderId.value && mode.value === "new" && defaultOrderTypeId.value) {
                const preferredType = orderTypes.value.find(
                    (type) => Number(type.id) === defaultOrderTypeId.value
                );

                if (preferredType) {
                    orderType.value =
                        preferredType.slug === "dine_in"
                            ? "Dine In"
                            : preferredType.slug === "pickup"
                                ? "Pickup"
                                : "Delivery";
                }
            }
            const activeOrderType = orderTypes.value.find(
                (type) => normalizeOrderTypeSlug(type.slug) === normalizeOrderTypeSlug(orderType.value)
            );
            setAsDefaultOrderType.value =
                !!defaultOrderTypeId.value &&
                Number(activeOrderType?.id || 0) === Number(defaultOrderTypeId.value);
        }

        if (Array.isArray(bootstrap.delivery_platforms) && bootstrap.delivery_platforms.length > 0) {
            deliveryPlatforms.value = bootstrap.delivery_platforms;
            console.log("Loaded delivery platforms:", deliveryPlatforms.value);
        }

        if (Array.isArray(bootstrap.delivery_executives)) {
            deliveryExecutives.value = bootstrap.delivery_executives;
            console.log("Loaded delivery executives:", deliveryExecutives.value.length);
        }

        if (bootstrap.branch) {
            branchLat.value = bootstrap.branch.lat !== undefined && bootstrap.branch.lat !== null
                ? Number(bootstrap.branch.lat)
                : null;
            branchLng.value = bootstrap.branch.lng !== undefined && bootstrap.branch.lng !== null
                ? Number(bootstrap.branch.lng)
                : null;
        }

        if (Array.isArray(bootstrap.waiters) && bootstrap.waiters.length > 0) {
            waiters.value = bootstrap.waiters;
        }

        if (Array.isArray(bootstrap.taxes) && bootstrap.taxes.length > 0) {
            availableTaxes.value = bootstrap.taxes;
            calculateTaxes();
        }
    } else {
        console.warn("Bootstrap data is null or undefined!");
    }
};

// Fetch order and load KOT items into cart
const loadOrderData = async (targetOrderId = null) => {
    const activeOrderId = targetOrderId || orderId.value;
    if (!activeOrderId) {
        return; // No order ID, skip loading order data
    }

    try {
        const response = await axios.get(`/api/pos/orders/${activeOrderId}`);
        const payload = response.data?.data?.order || null;

        if (response.data.success && payload) {
            applyOrderPayload(payload, activeOrderId);
        }
    } catch (error) {
        console.error("Error loading order data:", error);
    }
};

const applyOrderPayload = (payload, activeOrderId) => {
    if (!payload) {
        return;
    }

    order.value = payload.id;
    orderNumber.value = payload.formatted_order_number || payload.order_number || orderNumber.value;
    orderLifecycleStatus.value = payload.status || "";
    customerId.value = payload.customer_id || payload.customer?.id || null;
    customer.value = normalizeCustomerPayload(payload.customer);
    orderStatus.value = payload.order_status || "";
    orderPermissions.value = {
        can_update_order: !!payload.permissions?.can_update_order,
        can_delete_order: !!payload.permissions?.can_delete_order,
        can_edit_billed_order: !!payload.permissions?.can_edit_billed_order,
        can_delete_kot_item: !!payload.permissions?.can_delete_kot_item,
        can_redeem_reward_points: payload.permissions?.can_redeem_reward_points !== false,
    };

    // Restore reward state from server order data
    rewardPointDiscount.value = Number(payload.reward_point_discount || 0);
    rewardPointsRedeemed.value = Number(payload.reward_points_redeemed || 0);
    rewardPointsEarned.value = Number(payload.reward_points_earned || 0);
    kotGroups.value = Array.isArray(payload.kots) ? payload.kots : [];
    deliveryAddress.value = payload.delivery_address || payload.customer?.delivery_address || payload.customer?.address || "";
    customerPhone.value = payload.customer_phone || "";
    customerLat.value = payload.customer_lat !== undefined && payload.customer_lat !== null
        ? Number(payload.customer_lat)
        : null;
    customerLng.value = payload.customer_lng !== undefined && payload.customer_lng !== null
        ? Number(payload.customer_lng)
        : null;

    // Load order details
    waiterId.value = payload.waiter_id || "";
    orderNote.value = payload.note || "";
    tipAmount.value = Number(payload.tip_amount || 0);
    extraCharges.value = Array.isArray(payload.extra_charges) ? payload.extra_charges : [];
    pickupDateTime.value = payload.pickup_datetime || "";

    // Legacy parity (Pos.php mount): hydrate per-order custom extras when the
    // server includes them (only sent if allow_custom_order_extras is on).
    if (payload.allow_custom_order_extras !== undefined) {
        allowCustomOrderExtras.value = !!payload.allow_custom_order_extras;
    }
    customExtras.value = Array.isArray(payload.custom_extras)
        ? payload.custom_extras.map((row) => ({
            amount: Number(row?.amount || 0),
            note: String(row?.note || ""),
        }))
        : [];

    const selectedType =
        (bootstrapData.value?.order_types || []).find(
            (type) => Number(type.id) === Number(payload.order_type_id)
        ) || null;

    if (selectedType) {
        orderTypeId.value = selectedType.id;
        orderType.value =
            selectedType.slug === "dine_in"
                ? "Dine In"
                : selectedType.slug === "pickup"
                    ? "Pickup"
                    : "Delivery";
    } else if (payload.order_type) {
        const fallbackType = String(payload.order_type || "").toLowerCase();
        orderType.value =
            fallbackType === "dine_in"
                ? "Dine In"
                : fallbackType === "pickup"
                    ? "Pickup"
                    : fallbackType === "delivery"
                        ? "Delivery"
                        : orderType.value;
    }

    selectedDeliveryApp.value = payload.delivery_app_id
        ? String(payload.delivery_app_id)
        : "default";
    setAsDefaultOrderType.value =
        !!defaultOrderTypeId.value &&
        Number(payload.order_type_id || 0) === Number(defaultOrderTypeId.value);
    selectedDeliveryExecutive.value = payload.delivery_executive_id
        ? Number(payload.delivery_executive_id)
        : "";
    deliveryFee.value = Number(payload.delivery_fee || 0);

    // Legacy parity (Pos.php mount): hydrate table_id and table_code so the
    // "Table X" badge displays correctly in the OrderPanel for linked orders.
    // This ensures table assignment survives order reloads via /pos/kot/{id}.
    currentTableId.value = payload.table_id ? Number(payload.table_id) : null;
    currentTable.value = payload.table_code ? String(payload.table_code) : "";

    const lines = Array.isArray(payload.lines) ? payload.lines : [];
    const loadedItems = lines.map((line, index) => {
        const quantity = Number(line.qty || 1);
        const lineUnitPrice = Number(line.unit_price || 0);
        const lineAmount = Number(line.amount || 0);
        const fallbackUnitPrice = quantity > 0 && lineAmount > 0
            ? lineAmount / quantity
            : 0;
        const resolvedUnitPrice = lineUnitPrice > 0 ? lineUnitPrice : fallbackUnitPrice;
        const parsedPackId = parseComboPackIdFromInstanceKey(line.combo_instance_key);
        const normalizedComboPackId =
            line.combo_pack_id !== undefined && line.combo_pack_id !== null
                ? Number(line.combo_pack_id)
                : parsedPackId;
        const normalizedComboInstanceKey =
            line.combo_instance_key ||
            (normalizedComboPackId ? `legacy_pack_${normalizedComboPackId}` : null);
        const normalizedComboPackName =
            line.combo_pack_name ||
            (normalizedComboPackId
                ? (comboPacks.value.find((p) => Number(p.id) === normalizedComboPackId)?.name || "Combo Pack")
                : null);

        return {
            id:
                line.order_item_id !== undefined &&
                    line.order_item_id !== null
                    ? `order_item_${line.order_item_id}`
                    : `loaded_${line.menu_item_id}_${index}`,
            order_item_id: line.order_item_id || null,
            kot_item_id: line.kot_item_id || null,
            menu_item_id: Number(line.menu_item_id),
            name: line.item_name || "Unknown Item",
            price: resolvedUnitPrice,
            base_unit_price: resolvedUnitPrice,
            quantity,
            variant_id: line.menu_item_variation_id || 0,
            modifier_id: 0,
            note: line.note || "",
            combo_pack_id: normalizedComboPackId || null,
            combo_instance_key: normalizedComboInstanceKey,
            combo_pack_name: normalizedComboPackName,
            combo_discount:
                line.combo_discount !== undefined && line.combo_discount !== null
                    ? Number(line.combo_discount)
                    : null,
            combo_original_unit_price:
                line.combo_original_unit_price !== undefined &&
                    line.combo_original_unit_price !== null
                    ? Number(line.combo_original_unit_price)
                    : null,
            modifier_option_quantities:
                line.modifier_option_quantities || {},
            line_key:
                line.order_item_id !== undefined && line.order_item_id !== null
                    ? `order_item_${line.order_item_id}`
                    : createCartLineKey(
                        line.menu_item_id,
                        line.menu_item_variation_id || 0,
                        0
                    ),
        };
    });

    // Legacy parity (Pos.php mount): on the /pos/kot/{id} "New KOT" route
    // (no ?show-order-detail=true) we keep order context — customer, waiter,
    // order type, note, lifecycle status — but must NOT populate the cart from
    // existing items. The user adds new lines and the server appends them.
    if (isNewKotMode.value) {
        cartItems.value = [];
        saveCartToStorage([]);
    } else {
        cartItems.value = loadedItems;
        saveCartToStorage(cartItems.value);
    }
    calculateTaxes();
    orderId.value = String(activeOrderId);

    console.log("Order data loaded successfully:", payload);
    syncRewardState(customerId.value || customer.value?.id || null);
};

const applySelectedTable = async (tableCode, tableId, targetActiveOrderId = null) => {
    const previousTableCode = currentTable.value;
    const previousTableId = currentTableId.value;

    currentTable.value = tableCode;
    currentTableId.value = tableId ? Number(tableId) : null;

    if (targetActiveOrderId) {
        await loadOrderData(targetActiveOrderId);
        return;
    }

    const activeOrderId = orderId.value ? Number(orderId.value) : null;
    if (activeOrderId) {
        try {
            await axios.post(`/api/pos/orders/${activeOrderId}/table`, {
                table_id: currentTableId.value || null,
            });

            await loadOrderData(activeOrderId);
        } catch (error) {
            currentTable.value = previousTableCode;
            currentTableId.value = previousTableId;

            const errorMessage =
                error?.response?.data?.message ||
                error?.message ||
                "Failed to update table";

            showPosAlert("error", errorMessage);
        }

        return;
    }

    // No active order on this table: keep the current draft cart and just attach the table.
    orderId.value = null;
    order.value = null;
};

// Load initial data
onMounted(async () => {
    if (!bootstrapData.value) {
        const remote = await loadBootstrapFromAjax();
        if (remote) {
            bootstrapData.value = remote;
        }
    }

    const defaultOrderType =
        mode.value === "delivery"
            ? "Delivery"
            : mode.value === "pickup"
                ? "Pickup"
                : "Dine In";
    orderType.value = defaultOrderType;

    // Load order data if present in bootstrap or URL
    if (initialOrderData.value) {
        applyOrderPayload(initialOrderData.value, initialOrderData.value.id);

        const missingExtendedContract =
            !Array.isArray(initialOrderData.value.kots) ||
            !initialOrderData.value.permissions;

        if (missingExtendedContract) {
            await loadOrderData(initialOrderData.value.id);
        }
    } else if (orderId.value) {
        await loadOrderData();
    }

    if (!orderId.value && initialTableData.value?.id) {
        currentTableId.value = Number(initialTableData.value.id);
        currentTable.value = String(initialTableData.value.table_code || "");
    }

    // FIX: Cart persistence (only restore if editing existing order)
    // In Speeder, cart only loads if you're editing an order
    // Fresh POS sessions start with empty cart (no localStorage restore)
    // Cart is only saved to database after KOT/Bill button is clicked
    if (orderId.value) {
        // Editing existing order - cart data already loaded via loadOrderData() above
        // which populates cartItems from the API response
        console.log("Editing existing order - cart loaded from API");
    } else {
        // Fresh POS session - don't restore cart from localStorage
        // This prevents stale items from previous sessions
        console.log("Fresh POS session - cart starts empty");
    }

    // Customer assignment is order-scoped. Fresh POS sessions always start with
    // no customer so a linked-order assignment never leaks into the next order.
    if (!orderId.value && !initialOrderData.value) {
        clearCustomerFromStorage();
    }

    // Load restaurant and menu data (synchronous, reads from bootstrap only)
    loadRestaurantData();
    loadMenuData();

    // Parallelize independent async API calls to avoid sequential delays
    const asyncTasks = [];

    // Queue cancel reasons API call
    asyncTasks.push(loadCancelReasons());

    // Queue order number API call (conditional on online/offline state)
    if (isOnline.value && !orderNumber.value) {
        asyncTasks.push(fetchNewOrderNumber());
    } else if (!isOnline.value && !orderNumber.value) {
        // If offline and no order number, try to load last one or increment
        const lastOrderNumber = loadOrderNumberFromStorage();
        if (lastOrderNumber) {
            asyncTasks.push(incrementOrderNumberOffline());
        } else {
            // Start with a default offline order number
            orderNumber.value = "Order #001";
            saveOrderNumberToStorage(orderNumber.value);
        }
    }

    // Wait for all async tasks to complete in parallel
    await Promise.all(asyncTasks);

    // Sync pending operations if online
    if (isOnline.value && pendingOperations.value.length > 0) {
        syncPendingOperations(syncHandler);
    }

    // Mark loading as complete
    // FIX: If bootstrap was loaded inline, isLoading was set to false immediately
    // This prevents blank page while loadMenuData processes
    isLoading.value = false;
    console.log("POS App loaded successfully");

    // Sync reward points state after initial load
    syncRewardState();
});

// Reload menu data when coming back online
watch(isOnline, (newVal) => {
    if (newVal) {
        // When coming back online, refresh menu data
        loadMenuData();
        // Also sync pending operations
        if (pendingOperations.value.length > 0) {
            syncPendingOperations(syncHandler);
        }
    }
});
</script>

<style scoped></style>
