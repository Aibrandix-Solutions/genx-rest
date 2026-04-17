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
                :currency-symbol="currencySymbol" @update:search="search = $event" @update:menuId="menuId = $event"
                @update:filterCategories="filterCategories = $event" @add-to-cart="handleAddToCart" @reset="handleReset" />

            <OrderPanel class="w-full lg:basis-[30%] lg:max-w-[30%] min-w-0" :order-type="orderType"
                :order-number="orderNumber" :current-table="currentTable" :pax="pax" :waiter-id="waiterId"
                :waiters="waiters" :customer="customer" :order-types="orderTypes" :cart-items="cartItems" :taxes="taxes"
                :saving-action="savingAction" :extra-charges="extraCharges" :discount-amount="discountAmount"
                :discount-type="discountType" :discount-value="discountValue" :is-online="isOnline"
                :total-tax-amount="totalTaxAmount" :is-inclusive="false" :currency-symbol="currencySymbol"
                :order-status="orderStatus" :delivery-platforms="deliveryPlatforms"
                :selected-delivery-app="selectedDeliveryApp" :current-user="currentUser" :can-edit-waiter="canEditWaiter"
                :set-as-default-order-type="setAsDefaultOrderType" :delivery-executives="deliveryExecutives"
                :selected-delivery-executive="selectedDeliveryExecutive" :delivery-fee="deliveryFee"
                @update:orderType="orderType = $event"
                @show-add-customer="showAddCustomerModal = true" @remove-customer="handleRemoveCustomer"
                @select-table="handleSelectTable" @update:pax="pax = $event" @update:waiterId="handleWaiterUpdate"
                @update:orderStatus="handleOrderStatusUpdate" @add-note="handleAddNote"
                @update:selectedDeliveryExecutive="handleDeliveryExecutiveUpdate"
                @update:deliveryFee="handleDeliveryFeeUpdate"
                @update-quantity="handleUpdateQuantity" @update:selectedDeliveryApp="selectedDeliveryApp = $event"
                @update:setAsDefaultOrderType="setAsDefaultOrderType = $event" @increase-quantity="handleIncreaseQuantity"
                @decrease-quantity="handleDecreaseQuantity" @remove-item="handleRemoveItem" @save-order="handleSaveOrder"
                @update:extraCharges="extraCharges = $event" @apply-discount="handleApplyDiscount"
                @remove-discount="handleRemoveDiscount" :order="order" />
        </div>

        <!-- Modals -->
        <ReservationModal :show="showReservationModal" :reservation="reservation" @close="showReservationModal = false"
            @confirm-same="handleConfirmSameCustomer" @confirm-different="handleConfirmDifferentCustomer" />

        <TableChangeModal :show="showTableChangeConfirmationModal" :current-table="currentTable" :new-table="newTable"
            @close="showTableChangeConfirmationModal = false" @confirm="handleConfirmTableChange" />

        <AddCustomerModal :show="showAddCustomerModal" :customer="customer" @close="showAddCustomerModal = false"
            @save="handleSaveCustomer" />

        <AddNoteModal :show="showAddNoteModal" :note="orderNote" @close="showAddNoteModal = false" @save="handleSaveNote" />
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
    saveCustomer: saveCustomerToStorage,
    loadCustomer: loadCustomerFromStorage,
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
    };
};

const params = getUrlParams();
const orderId = ref(params.orderId);
const mode = ref(params.mode);

const getBootstrapData = () => {
    const mountEl = document.getElementById("pos-app");
    if (!mountEl) {
        return null;
    }

    try {
        const raw = mountEl.getAttribute("data-bootstrap");
        return raw ? JSON.parse(raw) : null;
    } catch (error) {
        console.error("Failed to parse POS bootstrap payload:", error);
        return null;
    }
};

const bootstrapData = ref(getBootstrapData());
const initialOrderData = computed(() => bootstrapData.value?.initial_order || null);

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

const retryLoadBootstrap = () => {
    bootstrapData.value = getBootstrapData();
    if (bootstrapData.value) {
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
const availableTaxes = ref([]);
const orderTypes = ref([]);
const deliveryPlatforms = ref([]);
const restaurant = ref(null);
const currencySymbol = ref("$");

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
const customerId = ref(null);
const orderStatus = ref("");
const deliveryExecutives = ref([]);
const selectedDeliveryExecutive = ref("");
const deliveryFee = ref(0);
// Modal state
const showReservationModal = ref(false);
const showTableChangeConfirmationModal = ref(false);
const showAddCustomerModal = ref(false);
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
const handleAddToCart = async (itemId, variantId = 0, modifierId = 0) => {
    console.log("addCartItems:", itemId, variantId, modifierId);
    const normalizedItemId = Number(itemId);
    const normalizedVariantId = Number(variantId || 0);
    const normalizedModifierId = Number(modifierId || 0);
    const lineKey = createCartLineKey(
        normalizedItemId,
        normalizedVariantId,
        normalizedModifierId
    );

    // Find item and determine the correct price
    const item = menuItems.value.find((i) => Number(i.id) === normalizedItemId);
    console.log("Found item:", item);

    if (item) {
        // Resolve the correct price based on variation
        let price = resolveContextualPrice(item);

        if (normalizedVariantId && item.variations && item.variations.length > 0) {
            const variation = item.variations.find((v) => Number(v.id) === normalizedVariantId);
            if (variation) {
                price = resolveContextualPrice(item, variation.id);
                console.log("Using variation price:", price);
            }
        }

        const existingItem = cartItems.value.find(
            (ci) => getCartLineKey(ci) === lineKey
        );
        if (existingItem) {
            existingItem.quantity++;
            console.log("Updated existing item:", existingItem);
        } else {
            const newCartItem = {
                id: normalizedItemId,
                menu_item_id: normalizedItemId,
                name: item.item_name || item.name || "Unknown Item",
                price: price,
                quantity: 1,
                variant_id: normalizedVariantId,
                modifier_id: normalizedModifierId,
                line_key: lineKey,
            };
            cartItems.value.push(newCartItem);
            console.log("Added new cart item:", newCartItem);
            console.log("All cart items:", cartItems.value);
        }
        // Save cart to localStorage
        saveCartToStorage(cartItems.value);

        // Play beep sound when item is added to cart
        playBeepSound();
    } else {
        console.error("Item not found with id:", itemId);
        console.log("Available menu items:", menuItems.value);
    }
};

const createCartLineKey = (itemId, variantId = 0, modifierId = 0) => {
    return `${itemId}:${Number(variantId || 0)}:${Number(modifierId || 0)}`;
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

const findCartLine = (itemId, variantId = 0, modifierId = 0) => {
    return cartItems.value.find(
        (ci) =>
            getCartLineKey(ci) === itemId ||
            (ci.id === itemId &&
                Number(ci.variant_id || 0) === Number(variantId || 0) &&
                Number(ci.modifier_id || 0) === Number(modifierId || 0))
    );
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

const handleAddNote = (noteData) => {
    // If noteData is an object with id and note, it's for a cart item
    if (noteData && typeof noteData === "object" && noteData.id) {
        // Find the cart item and update its note
        const cartItem = cartItems.value.find(
            (item) => item.id === noteData.id
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

const handleSaveNote = (note) => {
    orderNote.value = note;
    console.log("Order note saved:", note);
};

const handleIncreaseQuantity = (itemId) => {
    const item = findCartLine(itemId);
    if (item) {
        item.quantity++;
        // Save cart to localStorage
        saveCartToStorage(cartItems.value);
    }
};

const handleDecreaseQuantity = (itemId) => {
    const item = findCartLine(itemId);
    if (item && item.quantity > 1) {
        item.quantity--;
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
    if (item) {
        const newQty = parseInt(quantityData.quantity, 10);
        if (newQty > 0) {
            item.quantity = newQty;
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
    cartItems.value = cartItems.value.filter((ci) => getCartLineKey(ci) !== itemId);
    // Save cart to localStorage
    saveCartToStorage(cartItems.value);
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
    availableTaxes.value = [];
    orderId.value = null;
    orderStatus.value = "";
    selectedDeliveryExecutive.value = "";
    deliveryFee.value = 0;
    calculateTaxes();
};

const handleSaveOrder = async (...actions) => {
    // Create action key for tracking which button is being pressed
    const actionKey = actions.join("_") || "kot"; // e.g., "kot", "bill", "kot_print", "bill_payment", etc.
    savingAction.value = actionKey;
    try {
        // Validate cart has items
        if (!cartItems.value || cartItems.value.length === 0) {
            showPosAlert("error", "Cart is empty. Please add items before saving.");
            savingAction.value = null;
            return;
        }

        const actionList = Array.isArray(actions) ? actions : [];
        const action = actionList.includes("bill") ? "bill" : "kot";
        const openPayment = actionList.includes("payment");
        const selectedOrderType = resolveOrderType(orderType.value);

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
            order_id: orderId.value ? Number(orderId.value) : null,
            action,
            open_payment: openPayment,
            order_type_id: selectedOrderType?.id || null,
            delivery_app_id:
                normalizeOrderTypeSlug(selectedOrderType?.slug) === "delivery"
                    ? selectedDeliveryApp.value || "default"
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
            // Clear draft state after queueing for sync
            clearCartAfterSave();

            // Increment order number for next offline order
            await incrementOrderNumberOffline();
        } else {
            console.log("Order saved successfully:", result.data);

            // Clear cart after successful save (Speeder behavior)
            clearCartAfterSave();

            const orderIdToOpen = result.data.order_id;

            if (openPayment && orderIdToOpen) {
                const openedPayment = dispatchLivewireEvent("showPaymentModal", {
                    id: orderIdToOpen,
                });

                if (!openedPayment) {
                    window.location.href = `/orders/${orderIdToOpen}?payment=true`;
                }
            } else if (actionList.includes("bill") && orderIdToOpen) {
                const opened = dispatchLivewireEvent("showOrderDetail", {
                    id: orderIdToOpen,
                    fromPos: true,
                });

                if (!opened) {
                    window.location.href = `/orders/${orderIdToOpen}`;
                }
            }

            // Handle print action
            if (actionList.includes("print")) {
                // Determine which document to print (bill or KOT)
                const printUrl = actionList.includes("bill")
                    ? result.data.links?.bill
                    : result.data.links?.kot;

                if (printUrl) {
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
                    console.warn("Print URL not available in response");
                }
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
    // TODO: Implement API call to confirm table change
    console.log("confirmTableChange");
    await applySelectedTable(
        newTable.value,
        newTableId.value,
        newTableActiveOrderId.value
    );
    newTableActiveOrderId.value = null;
    showTableChangeConfirmationModal.value = false;
};

const handleSaveCustomer = async (customerData) => {
    // Customer is already created/updated in AddCustomerModal.
    // Here we only bind it to current POS state (legacy behavior).
    customer.value = { ...customerData };
    customerId.value = customerData?.id || null;
    saveCustomerToStorage(customer.value);
    showAddCustomerModal.value = false;
};

const handleRemoveCustomer = () => {
    customer.value = getEmptyCustomer();
    customerId.value = null;
    clearCustomerFromStorage();
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
const loadRestaurantData = async () => {
    const bootstrap = bootstrapData.value;
    if (bootstrap?.currency_symbol) {
        currencySymbol.value = bootstrap.currency_symbol;
    }

    if (bootstrap) {
        restaurant.value = {
            tax_mode: bootstrap.tax_mode || "item",
        };
    }
};

// Load menu data from cache or API
const loadMenuData = async () => {
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
        }

        if (
            Array.isArray(bootstrap.combo_packs) &&
            bootstrap.combo_packs.length > 0
        ) {
            comboPacks.value = bootstrap.combo_packs;
        }

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

            const defaultOrderTypeId = Number(bootstrap.pos_preferences.default_order_type_id || 0);
            if (!orderId.value && mode.value === "new" && defaultOrderTypeId > 0) {
                const preferredType = orderTypes.value.find(
                    (type) => Number(type.id) === defaultOrderTypeId
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
        }

        if (Array.isArray(bootstrap.delivery_platforms) && bootstrap.delivery_platforms.length > 0) {
            deliveryPlatforms.value = bootstrap.delivery_platforms;
            console.log("Loaded delivery platforms:", deliveryPlatforms.value);
        }

        if (Array.isArray(bootstrap.delivery_executives)) {
            deliveryExecutives.value = bootstrap.delivery_executives;
            console.log("Loaded delivery executives:", deliveryExecutives.value.length);
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
    customerId.value = payload.customer_id || payload.customer?.id || null;
    customer.value = normalizeCustomerPayload(payload.customer);
    orderStatus.value = payload.order_status || "";

    // Load order details
    waiterId.value = payload.waiter_id || "";
    orderNote.value = payload.note || "";

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
    selectedDeliveryExecutive.value = payload.delivery_executive_id
        ? Number(payload.delivery_executive_id)
        : "";
    deliveryFee.value = Number(payload.delivery_fee || 0);

    const lines = Array.isArray(payload.lines) ? payload.lines : [];
    const loadedItems = lines.map((line, index) => ({
        id:
            line.order_item_id !== undefined &&
                line.order_item_id !== null
                ? `order_item_${line.order_item_id}`
                : `loaded_${line.menu_item_id}_${index}`,
        menu_item_id: Number(line.menu_item_id),
        name: line.item_name || "Unknown Item",
        price: Number(line.unit_price || 0),
        quantity: Number(line.qty || 1),
        variant_id: line.menu_item_variation_id || 0,
        modifier_id: 0,
        note: line.note || "",
        combo_pack_id: line.combo_pack_id || null,
        combo_instance_key: line.combo_instance_key || null,
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
    }));

    cartItems.value = loadedItems;
    saveCartToStorage(cartItems.value);
    calculateTaxes();
    orderId.value = String(activeOrderId);

    console.log("Order data loaded successfully:", payload);
};

const applySelectedTable = async (tableCode, tableId, activeOrderId = null) => {
    currentTable.value = tableCode;
    currentTableId.value = tableId;

    if (activeOrderId) {
        await loadOrderData(activeOrderId);
        return;
    }

    // No active order on this table: keep the current draft cart and just attach the table.
    orderId.value = null;
    order.value = null;
};

// Load initial data
onMounted(async () => {
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
    } else if (orderId.value) {
        await loadOrderData();
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

    // Load customer from localStorage only for fresh orders.
    // Editing an existing order should always use the order payload customer.
    if (!orderId.value && !initialOrderData.value) {
        const savedCustomer = loadCustomerFromStorage();
        if (savedCustomer) {
            customer.value = savedCustomer;
            customerId.value = savedCustomer.id || null;
            console.log("Loaded customer from localStorage:", savedCustomer);
        }
    }

    // Load restaurant data (from API)
    await loadRestaurantData();

    // Load menu data (from cache first, then API if online)
    await loadMenuData();

    // Fetch initial order number
    if (isOnline.value && !orderNumber.value) {
        await fetchNewOrderNumber();
    } else if (!isOnline.value && !orderNumber.value) {
        // If offline and no order number, try to load last one or increment
        const lastOrderNumber = loadOrderNumberFromStorage();
        if (lastOrderNumber) {
            await incrementOrderNumberOffline();
        } else {
            // Start with a default offline order number
            orderNumber.value = "Order #001";
            saveOrderNumberToStorage(orderNumber.value);
        }
    }

    // Sync pending operations if online
    if (isOnline.value && pendingOperations.value.length > 0) {
        syncPendingOperations(syncHandler);
    }

    // Mark loading as complete
    // FIX: If bootstrap was loaded inline, isLoading was set to false immediately
    // This prevents blank page while loadMenuData processes
    isLoading.value = false;
    console.log("POS App loaded successfully");
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
