<template>
    <div v-if="show" class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @click.self="handleClose">
        <div class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75" @click="handleClose"></div>

        <div
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-md sm:max-h-[85vh] sm:mx-auto flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Select Room</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Choose a checked-in guest for room service.
                </p>
            </div>

            <div class="px-6 pt-4 pb-2 border-b border-gray-100 dark:border-gray-700/80">
                <label for="room-service-search" class="sr-only">Search rooms</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-4.35-4.35M15.5 10.5a5 5 0 1 0-10 0 5 5 0 0 0 10 0z" />
                    </svg>
            
                    <input id="room-service-search" v-model.trim="searchQuery" type="search"
                        autocomplete="off" placeholder="Search by room number or guest name..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-skin-base focus:outline-none focus:ring-1 focus:ring-skin-base dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500" />
                </div>
                <p v-if="!loading && localReservations.length > 0" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ filteredReservations.length }} of {{ localReservations.length }} checked-in
                    {{ filteredReservations.length === 1 ? "room" : "rooms" }}
                </p>
            </div>

            <div class="px-6 py-4 overflow-y-auto flex-1 min-h-0">
                <div v-if="loading" class="text-center text-sm text-gray-500 py-8">Loading rooms...</div>
                <div v-else-if="localReservations.length === 0" class="text-center text-sm text-gray-500 py-8">
                    No checked-in rooms found.
                </div>
                <div v-else-if="filteredReservations.length === 0" class="text-center text-sm text-gray-500 py-8">
                    No rooms match "{{ searchQuery }}".
                </div>
                <ul v-else class="grid grid-cols-1 gap-2">
                    <li v-for="reservation in filteredReservations" :key="reservation.id">
                        <button type="button"
                            class="flex w-full items-center justify-between rounded-lg border p-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                            :class="Number(selectedId) === Number(reservation.id)
                                ? 'border-skin-base bg-skin-base/5 dark:border-skin-base'
                                : 'border-gray-200 dark:border-gray-600'"
                            @click="selectReservation(reservation)">
                            <div class="min-w-0 pr-2">
                                <div class="font-semibold text-gray-800 dark:text-gray-200">
                                    Room {{ reservation.room_number || "?" }}
                                    <span v-if="reservation.room_type_name"
                                        class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                        ({{ reservation.room_type_name }})
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 truncate"
                                    :title="reservation.guest_name || 'Guest'">
                                    {{ reservation.guest_name || "Guest" }}
                                </div>
                            </div>
                            <span
                                class="shrink-0 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                Checked In
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <button type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                    @click="handleClose">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";

const props = defineProps({
    show: { type: Boolean, default: false },
    reservations: { type: Array, default: () => [] },
    selectedId: { type: [Number, String, null], default: null },
    refreshOnOpen: { type: Boolean, default: true },
});

const emit = defineEmits(["close", "select", "update:reservations"]);

const loading = ref(false);
const localReservations = ref([]);
const searchQuery = ref("");

const normalizeSearchText = (value) =>
    String(value || "")
        .trim()
        .toLowerCase();

const filteredReservations = computed(() => {
    const query = normalizeSearchText(searchQuery.value);
    const rows = localReservations.value;

    if (!query) {
        return rows;
    }

    return rows.filter((reservation) => {
        const roomNumber = normalizeSearchText(reservation?.room_number);
        const guestName = normalizeSearchText(reservation?.guest_name);
        const roomType = normalizeSearchText(reservation?.room_type_name);
        const label = normalizeSearchText(reservation?.label);

        return (
            roomNumber.includes(query) ||
            guestName.includes(query) ||
            roomType.includes(query) ||
            label.includes(query)
        );
    });
});

const loadReservations = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/api/pos/hotel/in-house-reservations");
        const rows = Array.isArray(data?.data) ? data.data : [];
        localReservations.value = rows;
        emit("update:reservations", rows);
    } catch (error) {
        console.error("Failed to load in-house reservations:", error);
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.show,
    async (visible) => {
        if (!visible) {
            searchQuery.value = "";
            return;
        }

        searchQuery.value = "";

        if (Array.isArray(props.reservations) && props.reservations.length > 0) {
            localReservations.value = [...props.reservations];
        } else {
            localReservations.value = [];
        }

        if (props.refreshOnOpen) {
            await loadReservations();
        }
    }
);

watch(
    () => props.reservations,
    (rows) => {
        if (Array.isArray(rows)) {
            localReservations.value = [...rows];
        }
    },
    { deep: true }
);

const handleClose = () => {
    emit("close");
};

const selectReservation = (reservation) => {
    emit("select", reservation);
    emit("close");
};
</script>
