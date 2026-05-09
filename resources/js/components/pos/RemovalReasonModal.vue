<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50" @click="$emit('close')"></div>

        <!-- Modal -->
        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Remove KOT Item</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Provide a reason for removing or reducing KOT items. This will be logged in the KOT Adjustments report.
                    </p>
                </div>
            </div>

            <textarea
                v-model="reason"
                rows="3"
                placeholder="Enter reason for removal..."
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none resize-none"
                autofocus
            ></textarea>

            <div v-if="reasonError" class="mt-1 text-xs text-red-500">{{ reasonError }}</div>

            <div class="flex gap-3 mt-4">
                <button
                    type="button"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                    @click="$emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleConfirm"
                >
                    Remove Item
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);

const reason = ref('');
const reasonError = ref('');

// Reset state when modal opens
watch(() => props.show, (val) => {
    if (val) {
        reason.value = '';
        reasonError.value = '';
    }
});

const handleConfirm = () => {
    if (!reason.value.trim()) {
        reasonError.value = 'Please enter a reason for removing this item.';
        return;
    }
    emit('confirm', reason.value.trim());
};
</script>
