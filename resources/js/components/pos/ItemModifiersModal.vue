<template>
    <div v-if="show" class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @click.self="handleClose">
        <div class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75" @click="handleClose"></div>

        <div
            class="mb-6 bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-2xl sm:mx-auto">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <img v-if="item?.item_photo_url" :src="item.item_photo_url" :alt="item?.item_name"
                            class="h-12 w-12 object-cover rounded-md" />
                        <div v-else class="h-12 w-12 bg-gray-100 dark:bg-gray-700 rounded-md"></div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                Item Modifiers
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ item?.item_name }}<span v-if="variationName"> — {{ variationName }}</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="handleClose"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 -m-1"
                        title="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div v-if="!groups.length" class="text-center py-6 text-sm text-gray-500 dark:text-gray-400">
                    No modifiers available for this item.
                </div>

                <div v-for="group in groups" :key="group.id"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-700/30 overflow-hidden"
                    :class="missingRequired[group.id] ? 'ring-2 ring-red-400 dark:ring-red-500/70' : ''">
                    <!-- Group header -->
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-white dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ group.name }}</h3>
                            <span v-if="group.is_required"
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                Required
                            </span>
                            <span v-if="!group.allow_multiple_selection"
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                Pick one
                            </span>
                            <span v-else
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                Multi
                            </span>
                        </div>
                        <div v-if="missingRequired[group.id]" class="text-[11px] text-red-600 dark:text-red-400 font-medium">
                            Please pick at least one
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="option in group.options" :key="option.id"
                            class="flex items-center justify-between gap-3 px-4 py-2.5 transition"
                            :class="[
                                option.is_available === false ? 'opacity-50' : 'hover:bg-white dark:hover:bg-gray-700/40',
                                Number(selectedQty[option.id] || 0) > 0 ? 'bg-skin-base/5 dark:bg-skin-base/10' : ''
                            ]">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="inline-block w-2 h-2 rounded-full"
                                    :class="Number(selectedQty[option.id] || 0) > 0 ? 'bg-skin-base' : 'bg-gray-300 dark:bg-gray-600'"></span>
                                <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ option.name }}</span>
                                <span v-if="Number(option.price || 0) > 0"
                                    class="text-xs text-gray-500 dark:text-gray-400">
                                    +{{ currencySymbol }} {{ formatPrice(option.price) }}
                                </span>
                                <span v-if="option.is_available === false"
                                    class="ml-1 text-[10px] uppercase tracking-wide text-red-500">N/A</span>
                            </div>

                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" @click="decrementOption(group, option)"
                                    :disabled="option.is_available === false || Number(selectedQty[option.id] || 0) <= 0"
                                    class="w-7 h-7 inline-flex items-center justify-center rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </button>
                                <span class="min-w-6 text-center text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {{ Number(selectedQty[option.id] || 0) }}
                                </span>
                                <button type="button" @click="incrementOption(group, option)"
                                    :disabled="option.is_available === false"
                                    class="w-7 h-7 inline-flex items-center justify-center rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-700 dark:text-gray-200">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Unit total</span>
                    <span class="ml-2 font-semibold">
                        {{ currencySymbol }} {{ formatPrice(unitTotalPreview) }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="handleClose"
                        class="px-3 py-1.5 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-900 dark:text-gray-100">
                        Cancel
                    </button>
                    <button type="button" @click="handleSave" :disabled="saving"
                        class="px-4 py-1.5 text-sm font-medium rounded-md bg-skin-base hover:bg-skin-base/90 text-white disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1.5">
                        <svg v-if="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Add to cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
    variationId: { type: [Number, String, null], default: null },
    variationName: { type: String, default: "" },
    basePrice: { type: [Number, String], default: 0 },
    currencySymbol: { type: String, default: "$" },
});

const emit = defineEmits(["close", "save"]);

const selectedQty = ref({});
const missingRequired = ref({});
const saving = ref(false);

/**
 * Build the list of groups to display: base modifier_groups for the item PLUS any
 * variation-specific groups for the chosen variation. Mirrors legacy
 * ItemModifiers::mount() which concatenates base + variation-scoped groups.
 */
const groups = computed(() => {
    const out = [];
    const item = props.item;
    if (!item) return out;

    const base = Array.isArray(item.modifier_groups) ? item.modifier_groups : [];
    base.forEach((g) => out.push(g));

    const vid = props.variationId ? String(props.variationId) : null;
    if (vid) {
        const map = item.variation_modifier_groups || {};
        const list = Array.isArray(map[vid]) ? map[vid] : [];
        list.forEach((g) => out.push(g));
    }

    return out;
});

const unitTotalPreview = computed(() => {
    let total = Number(props.basePrice || 0);
    groups.value.forEach((g) => {
        (g.options || []).forEach((opt) => {
            const q = Number(selectedQty.value[opt.id] || 0);
            if (q > 0) {
                total += Number(opt.price || 0) * q;
            }
        });
    });
    return Number(total.toFixed(2));
});

const formatPrice = (price) => Number(price || 0).toFixed(2);

const incrementOption = (group, option) => {
    if (option.is_available === false) return;
    const current = Number(selectedQty.value[option.id] || 0);
    setOptionQty(group, option.id, current + 1);
};

const decrementOption = (group, option) => {
    const current = Number(selectedQty.value[option.id] || 0);
    setOptionQty(group, option.id, Math.max(0, current - 1));
};

const setOptionQty = (group, optionId, qty) => {
    const allowMultiple = !!group.allow_multiple_selection;
    if (!allowMultiple && qty > 0) {
        (group.options || []).forEach((o) => {
            if (Number(o.id) !== Number(optionId)) {
                selectedQty.value[Number(o.id)] = 0;
            }
        });
    }
    selectedQty.value[Number(optionId)] = Math.max(0, qty);
    if (qty > 0) {
        missingRequired.value[group.id] = false;
    }
};

const validateRequired = () => {
    const failures = {};
    groups.value.forEach((g) => {
        if (!g.is_required) return;
        const hasSelection = (g.options || []).some((o) => Number(selectedQty.value[o.id] || 0) > 0);
        if (!hasSelection) failures[g.id] = true;
    });
    missingRequired.value = failures;
    return Object.keys(failures).length === 0;
};

const buildSelectionMap = () => {
    const map = {};
    Object.keys(selectedQty.value).forEach((k) => {
        const q = Number(selectedQty.value[k] || 0);
        if (q > 0) map[Number(k)] = q;
    });
    return map;
};

const handleSave = () => {
    if (!validateRequired()) return;
    saving.value = true;
    const payload = {
        modifierOptionQuantities: buildSelectionMap(),
        unitPrice: unitTotalPreview.value,
        groups: groups.value.map((g) => ({ id: g.id, name: g.name })),
    };
    emit("save", payload, () => {
        saving.value = false;
    });
};

const handleClose = () => {
    saving.value = false;
    emit("close");
};

/**
 * Apply preselected defaults when the modal opens (legacy ModifierOption.is_preselected).
 * Reset state when it closes.
 */
const seedSelections = () => {
    const next = {};
    groups.value.forEach((g) => {
        (g.options || []).forEach((opt) => {
            if (opt.is_preselected && opt.is_available !== false) {
                next[Number(opt.id)] = 1;
            }
        });
    });
    selectedQty.value = next;
    missingRequired.value = {};
};

watch(
    () => props.show,
    (isShowing) => {
        if (isShowing) {
            seedSelections();
        } else {
            saving.value = false;
        }
    }
);
</script>

<style scoped></style>
