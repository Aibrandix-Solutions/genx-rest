<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6"
        @keydown.esc.prevent="emitClose"
    >
        <div class="absolute inset-0 bg-gray-900/50" @click="emitClose"></div>

        <div
            class="relative w-full max-w-3xl max-h-[92vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-xl shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-label="Payment"
        >
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-5 h-5 text-skin-base shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5 5.5L9 14m0 0l-1.5 1.5M15 8l1.5-1.5M4 6h16v12H4z" />
                    </svg>
                    <div class="min-w-0">
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Payment</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ orderLabel }}</div>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-xl font-bold text-skin-base">{{ currencySymbol }}{{ formatMoney(dueAmount) }}</div>
                    <div v-if="saving" class="text-xs text-amber-600 dark:text-amber-400">Saving order…</div>
                </div>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-4">
                    <div class="flex gap-2">
                        <button type="button" class="flex-1 py-2 px-3 text-sm rounded-lg border bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                            Full Payment
                        </button>
                        <button
                            type="button"
                            class="flex-1 py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                            :disabled="saving || !orderId"
                            @click="$emit('open-advanced')"
                        >
                            Split Bill
                        </button>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <button
                            v-for="method in methods"
                            :key="method.id"
                            type="button"
                            class="p-2 text-center border rounded-lg text-sm"
                            :class="paymentMethod === method.id
                                ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200'
                                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200'"
                            @click="paymentMethod = method.id"
                        >
                            {{ method.label }}
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                        <input
                            v-model="amountInput"
                            type="text"
                            inputmode="decimal"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-lg"
                            @focus="amountTouched = true"
                        />
                    </div>

                    <div class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between"><span>Total</span><span>{{ currencySymbol }}{{ formatMoney(dueAmount) }}</span></div>
                        <div class="flex justify-between text-blue-600 dark:text-blue-400 font-medium">
                            <span>Payable Amount</span><span>{{ currencySymbol }}{{ formatMoney(payableAmount) }}</span>
                        </div>
                        <div class="flex justify-between text-red-600 dark:text-red-400">
                            <span>Due Amount</span><span>{{ currencySymbol }}{{ formatMoney(remainingDue) }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="qa in quickAmounts"
                            :key="qa"
                            type="button"
                            class="p-2 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm dark:border-gray-600 dark:text-gray-200"
                            @click="setAmount(qa)"
                        >
                            {{ currencySymbol }}{{ formatMoney(qa) }}
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="n in ['1','2','3','4','5','6','7','8','9','.','0','⌫']"
                            :key="n"
                            type="button"
                            class="p-3 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-lg dark:border-gray-600 dark:text-gray-200"
                            @click="appendKey(n)"
                        >
                            {{ n }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    class="flex-1 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                    :disabled="submitting"
                    @click="emitClose"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="flex-1 px-4 py-3 rounded-lg bg-skin-base text-white hover:opacity-90 disabled:opacity-50"
                    :disabled="!canSubmit"
                    @click="submit"
                >
                    {{ submitting ? 'Processing…' : 'Complete Payment' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    orderId: { type: [Number, String], default: null },
    orderNumber: { type: String, default: "" },
    dueAmount: { type: Number, default: 0 },
    currencySymbol: { type: String, default: "Rs" },
    saving: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "submit", "open-advanced"]);

const paymentMethod = ref("cash");
const amountInput = ref("");
const amountTouched = ref(false);

const methods = [
    { id: "cash", label: "Cash" },
    { id: "card", label: "Card" },
    { id: "upi", label: "UPI" },
    { id: "bank_transfer", label: "Bank Transfer" },
    { id: "due", label: "Due" },
];

const quickAmounts = computed(() => {
    const base = Math.max(0, Number(props.dueAmount || 0));
    const presets = [500, 1000, 2000, 5000];
    if (base > 0 && !presets.includes(Math.round(base))) {
        return [base, ...presets];
    }
    return presets;
});

const orderLabel = computed(() => props.orderNumber || (props.orderId ? `Order #${props.orderId}` : "New order"));

const payableAmount = computed(() => {
    const typed = Number(amountInput.value || 0);
    if (!Number.isFinite(typed) || typed < 0) {
        return 0;
    }
    return typed;
});

const remainingDue = computed(() => Math.max(0, Number(props.dueAmount || 0) - payableAmount.value));

const canSubmit = computed(() => {
    if (props.saving || props.submitting || !props.orderId) {
        return false;
    }
    if (paymentMethod.value === "due") {
        return true;
    }
    return payableAmount.value > 0;
});

const formatMoney = (value) => {
    const n = Number(value || 0);
    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const resetLocal = () => {
    paymentMethod.value = "cash";
    amountTouched.value = false;
    amountInput.value = Number(props.dueAmount || 0) > 0 ? String(Number(props.dueAmount || 0)) : "";
};

const setAmount = (value) => {
    amountTouched.value = true;
    amountInput.value = String(Number(value || 0));
};

const appendKey = (key) => {
    amountTouched.value = true;
    if (key === "⌫") {
        amountInput.value = String(amountInput.value || "").slice(0, -1);
        return;
    }
    if (key === "." && String(amountInput.value || "").includes(".")) {
        return;
    }
    amountInput.value = `${amountInput.value || ""}${key}`;
};

const emitClose = () => {
    if (!props.submitting) {
        emit("close");
    }
};

const submit = () => {
    if (!canSubmit.value) {
        return;
    }
    emit("submit", {
        order_id: Number(props.orderId),
        payment_method: paymentMethod.value,
        amount: paymentMethod.value === "due" ? 0 : payableAmount.value,
    });
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            resetLocal();
        }
    }
);

watch(
    () => props.dueAmount,
    (next) => {
        if (props.show && !amountTouched.value) {
            amountInput.value = Number(next || 0) > 0 ? String(Number(next || 0)) : "";
        }
    }
);
</script>
