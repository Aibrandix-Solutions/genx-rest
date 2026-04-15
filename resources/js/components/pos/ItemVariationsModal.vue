<template>
    <div v-if="show" class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @click.self="handleClose">
        <!-- Backdrop -->
        <div class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75" @click="handleClose">
        </div>

        <!-- Modal Content -->
        <div
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto overflow-y-auto">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <!-- Item Image -->
                    <div class="flex-shrink-0">
                        <img v-if="item?.item_photo_url" :src="item.item_photo_url" :alt="item?.item_name"
                            class="h-16 w-16 object-cover rounded-lg" />
                        <div v-else class="h-16 w-16 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <!-- Item Details -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Item Variations
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ item?.item_name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <!-- Table Header -->
                <div class="grid grid-cols-3 gap-4 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        ITEM NAME
                    </div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        PRICE
                    </div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        ACTION
                    </div>
                </div>

                <!-- Variations List -->
                <div v-if="item?.variations && item.variations.length > 0" class="space-y-3">
                    <div v-for="variation in item.variations" :key="variation.id"
                        class="grid grid-cols-3 gap-4 items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <!-- Variation Name -->
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            {{ variation.variation }}
                        </div>

                        <!-- Price -->
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ currencySymbol }} {{ formatPrice(variation.contextual_price ?? variation.price) }}
                        </div>

                        <!-- Select Button -->
                        <button @click="selectVariation(variation)" :disabled="loading"
                            class="inline-flex justify-center items-center px-4 py-2 bg-skin-base hover:bg-skin-base/90 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
                            <span v-if="!loading">Select</span>
                            <span v-else class="flex items-center gap-1">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">No variations available</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                <button @click="handleClose"
                    class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-900 dark:text-gray-100 text-sm font-medium rounded-lg transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
    currencySymbol: {
        type: String,
        default: "$",
    },
});

const emit = defineEmits(["close", "select-variation"]);

const loading = ref(false);

const formatPrice = (price) => {
    return parseFloat(price || 0).toFixed(2);
};

const selectVariation = (variation) => {
    loading.value = true;
    try {
        // Emit is synchronous; parent handler will call done() when async work completes
        emit("select-variation", variation, () => {
            loading.value = false;
        });
    } catch (error) {
        console.error("Error selecting variation:", error);
        loading.value = false;
    }
};

const handleClose = () => {
    loading.value = false;
    emit("close");
};

watch(
    () => props.show,
    (isShowing) => {
        if (!isShowing) {
            loading.value = false;
        }
    }
);
</script>

<style scoped></style>
