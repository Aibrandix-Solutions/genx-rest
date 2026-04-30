<template>
    <li class="group relative">
        <!-- Item without variations - clickable -->
        <template v-if="!hasVariations">
            <input type="checkbox" :id="`item-${item.id}`" :value="item.id" @click="handleClick" class="hidden peer"
                :disabled="loading" />
            <label :for="`item-${item.id}`" :class="[
                'block w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 relative outline-none cursor-pointer hover:shadow-md dark:hover:bg-gray-700/30 peer-checked:ring-2 peer-checked:ring-skin-base active:scale-95 focus-visible:scale-95 focus-visible:ring-2 focus-visible:ring-skin-base',
                item.in_stock === false ? 'bg-gray-100 dark:bg-gray-800' : 'bg-white dark:bg-gray-900',
            ]" tabindex="0">
                <!-- Loading Overlay -->
                <div v-if="loading"
                    class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-lg z-10 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-skin-base" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>

                <!-- Image -->
                <div class="relative aspect-square hidden md:block">
                    <img class="w-full h-full object-cover rounded-t-lg" :src="item.item_photo_url" :alt="item.item_name" />
                    <span v-if="item.type"
                        class="absolute top-1 right-1 bg-white/90 dark:bg-gray-800/90 rounded-full p-1 shadow-sm">
                        <img :src="item.type === 'veg'
                            ? '/img/veg.svg'
                            : '/img/non-veg.svg'
                            " class="h-4 w-4" :title="item.type === 'veg' ? 'Veg' : 'Non Veg'" alt="" />
                    </span>
                </div>

                <!-- Content -->
                <div class="p-2">
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white min-h-[2.5rem]">
                        {{ item.item_name }}
                    </h5>
                    <div v-if="item.in_stock === false" class="text-red-500">
                        Out of stock
                    </div>
                    <div v-else class="mt-1 flex items-center justify-between gap-2">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ currencySymbol }} {{ formatPrice(item.contextual_price ?? item.price ?? 0) }}
                        </span>
                    </div>
                </div>
            </label>
        </template>

        <!-- Item with variations - entire item is clickable -->
        <template v-else>
            <button type="button" @click="handleShowVariations" :disabled="loading || item.in_stock === false" :class="[
                'w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 relative outline-none cursor-pointer hover:shadow-md dark:hover:bg-gray-700/30 active:scale-95 focus-visible:scale-95 focus-visible:ring-2 focus-visible:ring-skin-base disabled:opacity-50 disabled:cursor-not-allowed',
                item.in_stock === false ? 'bg-gray-100 dark:bg-gray-800' : 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-850',
            ]" :title="item.in_stock === false ? 'Out of stock' : 'Click to select variation'">
                <!-- Loading Overlay -->
                <div v-if="loading"
                    class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-lg z-10 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-skin-base" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>

                <!-- Image -->
                <div class="relative aspect-square hidden md:block">
                    <img class="w-full h-full object-cover rounded-t-lg" :src="item.item_photo_url" :alt="item.item_name" />
                    <span v-if="item.type"
                        class="absolute top-1 right-1 bg-white/90 dark:bg-gray-800/90 rounded-full p-1 shadow-sm">
                        <img :src="item.type === 'veg'
                            ? '/img/veg.svg'
                            : '/img/non-veg.svg'
                            " class="h-4 w-4" :title="item.type === 'veg' ? 'Veg' : 'Non Veg'" alt="" />
                    </span>
                </div>

                <!-- Content -->
                <div class="p-2">
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white min-h-[2.5rem]">
                        {{ item.item_name }}
                    </h5>
                    <div v-if="item.in_stock === false" class="text-red-500">
                        Out of stock
                    </div>
                    <div v-else class="mt-1 flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-600 dark:text-gray-300 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-3 h-3">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                            </svg>
                            Show Variations
                        </span>
                    </div>
                </div>
            </button>
        </template>
    </li>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    item: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            name: "",
            price: 0,
            image: null,
            veg_type: null,
            variant_id: 0,
            modifier_id: 0,
        }),
    },
    currencySymbol: {
        type: String,
        default: "$",
    },
});

const emit = defineEmits(["add-to-cart", "show-variations"]);

const loading = ref(false);

const hasVariations = computed(() => {
    return (props.item.variations_count || 0) > 0;
});

const handleClick = async () => {
    if (props.item.in_stock === false) {
        return;
    }

    loading.value = true;
    try {
        emit(
            "add-to-cart",
            props.item.id,
            props.item.variant_id || 0,
            {}, // Empty options payload for items without configurable options
            () => {
                loading.value = false;
            }
        );
    } catch (error) {
        loading.value = false;
    }
};

const handleShowVariations = () => {
    if (props.item.in_stock === false) {
        return;
    }
    emit("show-variations", props.item);
};

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2);
};

const getVariationPriceRange = () => {
    if (!props.item.variations || props.item.variations.length === 0) {
        return formatPrice(props.item.price || 0);
    }

    const prices = props.item.variations.map(v => Number(v.price || v.contextual_price || 0));
    if (prices.length === 0) {
        return formatPrice(props.item.price || 0);
    }

    const minPrice = Math.min(...prices);
    const maxPrice = Math.max(...prices);

    if (minPrice === maxPrice) {
        return formatPrice(minPrice);
    }

    return `${formatPrice(minPrice)} - ${formatPrice(maxPrice)}`;
};
</script>

<style scoped></style>
