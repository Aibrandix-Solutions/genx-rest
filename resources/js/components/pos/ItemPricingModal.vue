<template>
    <div v-if="show" class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @click.self="handleClose">
        <div class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75" @click="handleClose"></div>

        <div
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto overflow-y-auto">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ itemName }}
                </div>

                <div class="mt-4 space-y-4 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Price</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">{{ currencySymbol }}</span>
                            <input type="number" v-model.number="form.unitPrice" step="0.01" min="0"
                                class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Item Discount (optional)</label>
                        <div class="flex gap-2">
                            <input type="number" v-model.number="form.discountValue" step="0.01" min="0"
                                class="w-2/3 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm"
                                placeholder="Enter Discount Value" />
                            <select v-model="form.discountType"
                                class="w-1/3 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm">
                                <option value="fixed">Fixed</option>
                                <option value="percent">Percent</option>
                            </select>
                        </div>
                    </div>

                    <div class="rounded-md bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm">
                        <div class="flex justify-between">
                            <span>Qty</span>
                            <span>{{ quantity }}</span>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span>Line total</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ currencySymbol }} {{ formatPrice(previewLineTotal) }}</span>
                        </div>
                        <div v-if="previewDiscount > 0" class="flex justify-between mt-1 text-green-600 dark:text-green-400">
                            <span>Item discount</span>
                            <span>-{{ currencySymbol }} {{ formatPrice(previewDiscount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
                <div class="flex justify-end gap-2 w-full">
                    <button type="button"
                        class="button-cancel inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600"
                        @click="handleClose">
                        Cancel
                    </button>
                    <button type="button"
                        class="text-white justify-center bg-skin-base hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm px-3 py-2"
                        :disabled="saving || form.unitPrice < 0"
                        @click="handleSave">
                        <span v-if="!saving">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { computeItemDiscountAmount, lineTotalAmount } from "../../utils/posItemPricing";

const props = defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
    currencySymbol: { type: String, default: "" },
});

const emit = defineEmits(["close", "save"]);

const saving = ref(false);
const form = ref({
    unitPrice: 0,
    discountType: "fixed",
    discountValue: null,
});

const itemName = computed(() => props.item?.name || "Item");
const quantity = computed(() => Number(props.item?.quantity || 1));

const previewItem = computed(() => ({
    price: Number(form.value.unitPrice || 0),
    quantity: quantity.value,
    discount_type: Number(form.value.discountValue || 0) > 0 ? form.value.discountType : null,
    discount_value: Number(form.value.discountValue || 0) > 0 ? Number(form.value.discountValue) : null,
}));

const previewDiscount = computed(() => computeItemDiscountAmount(previewItem.value));
const previewLineTotal = computed(() => lineTotalAmount(previewItem.value));

const formatPrice = (value) => Number(value || 0).toFixed(2);

watch(
    () => props.show,
    (isShowing) => {
        if (!isShowing) {
            saving.value = false;
            return;
        }

        const item = props.item || {};
        form.value = {
            unitPrice: Number(item.price || 0),
            discountType: item.discount_type || "fixed",
            discountValue: item.discount_value ? Number(item.discount_value) : null,
        };
    }
);

const handleClose = () => emit("close");

const handleSave = async () => {
    if (form.value.unitPrice < 0) {
        return;
    }

    saving.value = true;
    try {
        const discountValue = Number(form.value.discountValue || 0);
        const payload = {
            unit_price: Number(form.value.unitPrice || 0),
            discount_type: discountValue > 0 ? form.value.discountType : null,
            discount_value: discountValue > 0 ? discountValue : null,
        };

        await new Promise((resolve, reject) => {
            emit("save", payload, (err) => {
                if (err) {
                    reject(err);
                    return;
                }
                resolve();
            });
        });

        saving.value = false;
        handleClose();
    } catch (error) {
        console.error("Error saving item pricing:", error);
        saving.value = false;
    }
};
</script>
