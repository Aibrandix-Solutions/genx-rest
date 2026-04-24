<template>
    <div class="w-full min-w-0">
        <div data-has-alpine-state="true">
            <!-- Mobile Toggle Button -->
            <button @click="showMenu = !showMenu"
                class="fixed bottom-6 right-6 z-50 md:hidden bg-skin-base text-white rounded-full shadow-lg p-4 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-skin-base transition"
                aria-label="Toggle Menu" type="button">
                <!-- Hamburger Icon (visible when menu is closed) -->
                <svg v-show="!showMenu" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5">
                    </path>
                </svg>
                <!-- Close Icon (visible when menu is open) -->
                <svg v-show="showMenu" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Menu Panel -->
            <div :class="showMenu ? 'fixed inset-0 z-40 flex' : 'hidden md:flex'"
                class="md:flex flex-col bg-gray-50 lg:h-full w-full py-4 px-3 dark:bg-gray-900 transition-transform duration-300 md:static md:inset-auto md:z-auto md:translate-x-0 overflow-y-auto md:overflow-visible md:max-h-none"
                style="backdrop-filter: blur(2px)">
                <!-- Search and Reset -->
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1">
                        <form action="#" method="GET" @submit.prevent>
                            <label for="products-search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="products-search" v-model="localSearch" @input="handleSearch"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-gray-500 dark:focus:border-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 rounded-md shadow-sm block w-full pl-10 pr-3 py-2 border-gray-200 rounded-lg text-sm"
                                    placeholder="Search your menu item here" />
                            </div>
                        </form>
                    </div>

                    <button @click="handleReset"
                        class="text-white justify-center bg-skin-base hover:bg-skin-base/[.8] sm:w-auto dark:bg-skin-base dark:hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center px-3 py-2 gap-1 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z">
                            </path>
                            <path
                                d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466">
                            </path>
                        </svg>
                        Reset
                    </button>
                </div>

                <!-- Menu Filters -->
                <div
                    class="flex gap-2 mt-4 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 flex-wrap">
                    <button @click="handleMenuFilter(null)" :class="[
                        'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',
                        localMenuId === null
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                            : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                    ]">
                        Show All
                    </button>

                    <button v-for="menu in menus" :key="menu.id" @click="handleMenuFilter(menu.id)" :class="[
                        'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',
                        localMenuId === menu.id
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                            : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                    ]">
                        {{ menu.menu_name }}
                    </button>
                </div>

                <!-- Category Filters -->
                <div
                    class="flex gap-2 mt-4 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 flex-wrap">
                    <button @click="handleCategoryFilter(null)" :class="[
                        'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',
                        localCategoryId === null
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                            : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                    ]">
                        Show All
                    </button>

                    <button v-for="category in categories" :key="category.id" @click="handleCategoryFilter(category.id)"
                        :class="[
                            'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',
                            localCategoryId === category.id
                                ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                        ]">
                        {{ category.category_name }}
                        <span v-if="category.count !== undefined"
                            class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-full px-1 py-0.5 ml-1">
                            {{ category.count }}
                        </span>
                    </button>
                </div>

                <!-- Menu Items Grid -->
                <div class="mt-4">
                    <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-8 gap-3">
                        <MenuItem v-for="item in filteredItems" :key="item.id" :item="item"
                            :currency-symbol="currencySymbol" @add-to-cart="handleAddToCart"
                            @show-variations="handleShowVariations" />
                    </ul>
                    <div v-if="filteredItems.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No items found
                    </div>
                </div>

                <!-- Combo packs (parity with legacy pos/menu.blade.php) -->
                <div v-if="filteredComboPacks.length > 0" class="mt-8">
                    <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                        Combo packs
                    </h3>
                    <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
                        <li v-for="combo in filteredComboPacks" :key="'combo-' + combo.id">
                            <button type="button"
                                class="flex flex-col w-full text-left rounded-xl overflow-hidden shadow-sm border border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:shadow-md active:scale-[0.98] transition min-h-[11rem]"
                                @click="handleAddCombo(combo.id)">
                                <!-- Image (legacy: hidden when restaurant hides menu images on POS) -->
                                <div v-if="comboImageVisible(combo)" class="relative h-24 w-full shrink-0 bg-gray-100 dark:bg-gray-700">
                                    <img :src="combo.combo_image_url" :alt="combo.name || 'Combo'"
                                        class="h-full w-full object-cover" loading="lazy" />
                                    <span
                                        class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wide text-white bg-blue-500 rounded-md px-2 py-0.5 shadow-sm">Combo</span>
                                </div>
                                <div v-else
                                    class="relative h-16 w-full shrink-0 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/40 dark:to-indigo-900/30 flex items-center justify-center">
                                    <span
                                        class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wide text-white bg-blue-500 rounded-md px-2 py-0.5 shadow-sm">Combo</span>
                                </div>
                                <div class="p-3 flex flex-col flex-1 min-h-0 bg-gradient-to-b from-blue-50/80 to-white dark:from-blue-900/10 dark:to-gray-800">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2">{{ combo.name }}</h4>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-through">
                                        {{ currencySymbol }}{{ formatComboPrice(combo.regular_price) }}
                                    </div>
                                    <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ currencySymbol }}{{ formatComboPrice(combo.discounted_price) }}
                                    </div>
                                    <div v-if="comboPackDiscountCaption(combo)" class="text-xs text-blue-600 dark:text-blue-400 font-medium mt-0.5">
                                        {{ comboPackDiscountCaption(combo) }}
                                    </div>
                                    <div v-if="comboPreviewShown(combo).length" class="mt-2 pt-2 border-t border-blue-100 dark:border-blue-800 space-y-0.5">
                                        <div v-for="(row, idx) in comboPreviewShown(combo)" :key="idx"
                                            class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1">
                                            {{ Number(row.quantity || row.qty || 1) }} × {{ comboItemLineLabel(row) }}
                                        </div>
                                        <div v-if="comboPreviewMore(combo) > 0" class="text-[10px] text-gray-500 dark:text-gray-400">
                                            + {{ comboPreviewMore(combo) }} more
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- Item Variations Modal -->
        <ItemVariationsModal :show="showVariationsModal" :item="selectedItem" :currency-symbol="currencySymbol"
            @close="showVariationsModal = false" @select-variation="handleSelectVariationWithCallback" />
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import MenuItem from "./MenuItem.vue";
import ItemVariationsModal from "./ItemVariationsModal.vue";

const props = defineProps({
    search: {
        type: String,
        default: "",
    },
    menuId: {
        type: [Number, String, null],
        default: null,
    },
    filterCategories: {
        type: [Number, String, null],
        default: null,
    },
    menus: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    order: {
        type: Object,
        default: () => null,
    },
    currencySymbol: {
        type: String,
        default: "$",
    },
    comboPacks: {
        type: Array,
        default: () => [],
    },
    hideMenuItemImageOnPos: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "update:search",
    "update:menuId",
    "update:filterCategories",
    "add-to-cart",
    "add-combo-to-cart",
    "reset",
]);

const localSearch = ref(props.search);
const localMenuId = ref(props.menuId);
const localCategoryId = ref(props.filterCategories);

// Mobile menu state
const showMenu = ref(false);

// Variations modal state
const showVariationsModal = ref(false);
const selectedItem = ref(null);

// Watch for prop changes
watch(
    () => props.search,
    (newVal) => {
        localSearch.value = newVal;
    }
);

watch(
    () => props.menuId,
    (newVal) => {
        localMenuId.value = newVal;
    }
);

watch(
    () => props.filterCategories,
    (newVal) => {
        localCategoryId.value = newVal;
    }
);

// Filter items based on search, menu, and category
const filteredItems = computed(() => {
    let filtered = [...props.items];

    // Filter by search
    if (localSearch.value) {
        const searchLower = localSearch.value.toLowerCase();
        filtered = filtered.filter((item) =>
            (item.name || item.item_name || "")
                .toLowerCase()
                .includes(searchLower)
        );
    }

    // Filter by menu
    if (localMenuId.value !== null) {
        filtered = filtered.filter(
            (item) => item.menu_id === localMenuId.value
        );
    }

    // Filter by category
    if (localCategoryId.value !== null) {
        filtered = filtered.filter(
            (item) => item.item_category_id === localCategoryId.value
        );
    }

    return filtered;
});

const filteredComboPacks = computed(() => {
    const packs = Array.isArray(props.comboPacks) ? props.comboPacks : [];
    if (!localSearch.value) {
        return packs;
    }
    const q = localSearch.value.toLowerCase();
    return packs.filter((p) => (p.name || "").toLowerCase().includes(q));
});

const formatComboPrice = (value) => {
    const n = Number(value || 0);
    return n.toFixed(2);
};

const comboImageVisible = (combo) => {
    const url = combo?.combo_image_url;
    return !props.hideMenuItemImageOnPos && typeof url === "string" && url.trim().length > 0;
};

const comboPreviewRows = (combo) => {
    return Array.isArray(combo?.items) ? combo.items : [];
};

const comboPreviewShown = (combo) => {
    return comboPreviewRows(combo).slice(0, 3);
};

const comboPreviewMore = (combo) => {
    return Math.max(0, comboPreviewRows(combo).length - 3);
};

const comboItemLineLabel = (row) => {
    const name = row?.item_name || "";
    const v = row?.variation_name || "";
    const s = v ? `${name} — ${v}` : name;
    return s || "Item";
};

/** Percent packs: show "% off". Fixed packs: show money saved on the pack (avoid implying %-only discount). */
const comboPackDiscountCaption = (combo) => {
    const type = String(combo?.discount_type || "").toLowerCase();
    if (type === "percent" && Number(combo?.discount_percent || 0) > 0) {
        return `${Math.round(Number(combo.discount_percent))}% off`;
    }
    const save = Number(combo?.regular_price || 0) - Number(combo?.discounted_price || 0);
    if (save > 0.005) {
        return `Save ${props.currencySymbol}${formatComboPrice(save)}`;
    }
    return "";
};

const handleAddCombo = (comboId) => {
    emit("add-combo-to-cart", comboId);
    showMenu.value = false;
};

const handleSearch = () => {
    emit("update:search", localSearch.value);
};

const handleMenuFilter = (menuId) => {
    localMenuId.value = menuId;
    emit("update:menuId", menuId);
};

const handleCategoryFilter = (categoryId) => {
    localCategoryId.value = categoryId;

    emit("update:filterCategories", categoryId);
};

const handleAddToCart = (itemId, variantId, modifierId, done) => {
    console.log("itemId", itemId);
    console.log("variantId", variantId);
    console.log("modifierId", modifierId);
    emit("add-to-cart", itemId, variantId, modifierId);
    // Close menu on mobile after adding to cart
    showMenu.value = false;
    if (typeof done === "function") {
        done();
    }
};

const handleShowVariations = (item) => {
    selectedItem.value = item;
    showVariationsModal.value = true;
};

const handleSelectVariation = (variation) => {
    if (selectedItem.value) {
        console.log("itemId", selectedItem.value.id);
        console.log("variantId", variation.id);
        console.log("modifierId", 0);
        emit("add-to-cart", selectedItem.value.id, variation.id, 0);
    }
    // Close both the variations modal and menu after selection
    showVariationsModal.value = false;
    showMenu.value = false;
    // Done callback from ItemVariationsModal - call it to clear loading state
    // Note: the third parameter is the done callback passed by ItemVariationsModal
};

// Update the event handler signature to handle the done callback
const handleSelectVariationWithCallback = (variation, done) => {
    if (selectedItem.value) {
        console.log("itemId", selectedItem.value.id);
        console.log("variantId", variation.id);
        console.log("modifierId", 0);
        emit("add-to-cart", selectedItem.value.id, variation.id, 0);
    }
    // Close both the variations modal and menu after selection
    showVariationsModal.value = false;
    showMenu.value = false;
    // Call done callback to clear loading state in modal
    if (typeof done === 'function') {
        done();
    }
};

const handleReset = () => {
    localSearch.value = "";
    localMenuId.value = null;
    localCategoryId.value = null;
    emit("reset");
    emit("update:search", "");
    emit("update:menuId", null);
    emit("update:filterCategories", null);
};
</script>

<style scoped></style>
