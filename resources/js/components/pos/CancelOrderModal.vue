<template>
    <div v-if="show" class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @click.self="handleClose">
        <div class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75" @click="handleClose"></div>

        <div class="mb-6 bg-white dark:bg-gray-900 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-2xl sm:mx-auto">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Cancel Order
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Please select a reason for cancellation.
                </p>
            </div>

            <div class="px-6 py-5 space-y-5">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                    <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                        This action matches the legacy POS cancel flow and will mark the order as cancelled.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="cancel-reason">
                        Select Cancel Reason
                    </label>
                    <select
                        id="cancel-reason"
                        v-model="selectedReasonId"
                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">Select Cancel Reason</option>
                        <option v-for="reason in reasons" :key="reason.id" :value="String(reason.id)">
                            {{ reason.reason }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="cancel-reason-text">
                        Cancel Reason Text
                    </label>
                    <textarea
                        id="cancel-reason-text"
                        v-model="reasonText"
                        rows="4"
                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                        placeholder="Enter cancel reason..."
                    ></textarea>
                </div>

                <p v-if="validationMessage" class="text-sm text-red-600 dark:text-red-400">
                    {{ validationMessage }}
                </p>
            </div>

            <div class="flex flex-row justify-end gap-3 px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    @click="handleClose"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    @click="handleSave"
                >
                    Cancel Order
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
    reasons: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "save"]);

const selectedReasonId = ref("");
const reasonText = ref("");
const validationMessage = ref("");

const resetForm = () => {
    selectedReasonId.value = "";
    reasonText.value = "";
    validationMessage.value = "";
};

watch(
    () => props.show,
    (isShowing) => {
        if (isShowing) {
            resetForm();
        }
    }
);

const handleClose = () => {
    emit("close");
};

const handleSave = () => {
    const selectedReason = selectedReasonId.value ? Number(selectedReasonId.value) : null;
    const trimmedReasonText = reasonText.value.trim();

    if (!selectedReason && !trimmedReasonText) {
        validationMessage.value = "Please select a cancel reason or enter cancel reason text.";
        return;
    }

    validationMessage.value = "";
    emit("save", {
        cancelReasonId: selectedReason,
        cancelReasonText: trimmedReasonText,
    });
};

if (typeof window !== "undefined") {
    watch(
        () => props.show,
        (isShowing, _, onCleanup) => {
            if (isShowing) {
                const handleEscape = (e) => {
                    if (e.key === "Escape") {
                        handleClose();
                    }
                };

                window.addEventListener("keydown", handleEscape);
                onCleanup(() => {
                    window.removeEventListener("keydown", handleEscape);
                });
            }
        }
    );
}
</script>
