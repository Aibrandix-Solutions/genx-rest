<template>
    <div
        class="w-full min-w-0 flex flex-col bg-white border-l dark:border-gray-700 min-h-screen h-auto px-3 py-4 dark:bg-gray-800 overflow-x-hidden overflow-y-auto">
        <!-- Order Type -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 pb-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Order Type:</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ orderType }}
                    </span>
                </div>
                <button type="button" @click="showOrderTypeDropdown = !showOrderTypeDropdown"
                    class="text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-full transition-all">
                    {{ showOrderTypeDropdown ? "Close" : "Change" }}
                </button>
            </div>

            <div v-if="showOrderTypeDropdown" class="mt-3 grid grid-cols-1 gap-2">
                <label class="text-xs text-gray-600 dark:text-gray-400">Select Order Type</label>
                <select v-model="localOrderTypeId" @change="handleOrderTypeChange"
                    class="text-sm w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-gray-500">
                    <option v-if="loadingOrderTypes" value="">Loading...</option>
                    <option v-for="type in availableOrderTypes" :key="type.id" :value="type.id">
                        {{ type.order_type_name }}
                    </option>
                </select>

                <label class="flex items-center gap-2 cursor-pointer select-none w-full mt-1">
                    <input type="checkbox" v-model="localSetAsDefaultOrderType" @change="handleSetDefaultOrderType"
                        class="w-4 h-4 text-skin-base bg-gray-100 border-gray-300 rounded focus:ring-skin-base focus:ring-2 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-skin-base">
                    <span class="text-xs text-gray-700 dark:text-gray-300">Set as default</span>
                </label>

                <template v-if="selectedOrderTypeSlug === 'delivery'">
                    <label class="text-xs text-gray-600 dark:text-gray-400 mt-1">Platform</label>
                    <select v-model="localSelectedDeliveryApp" @change="handleSelectDeliveryPlatform"
                        class="text-sm w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="default">Default</option>
                        <option v-for="platform in availableDeliveryPlatforms" :key="platform.id"
                            :value="String(platform.id)">
                            {{ platform.name }}
                        </option>
                    </select>
                </template>
            </div>
        </div>

        <div v-if="selectedOrderTypeSlug === 'delivery' && canEditWaiter"
            class="mt-3 mb-3 flex items-center gap-2 text-gray-700 dark:text-gray-300">
            <svg class="w-6 h-6 transition duration-75 text-gray-500 dark:text-gray-400" fill="currentColor"
                version="1.0" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g transform="translate(0 512) scale(.1 -.1)">
                    <path
                        d="m2605 4790c-66-13-155-48-213-82-71-42-178-149-220-221-145-242-112-552 79-761 59-64 61-67 38-73-13-4-60-24-104-46-151-75-295-249-381-462-20-49-38-91-39-93-2-2-19 8-40 22s-54 30-74 36c-59 16-947 12-994-4-120-43-181-143-122-201 32-33 76-33 106 0 41 44 72 55 159 55h80v-135c0-131 1-137 25-160l24-25h231 231l24 25c24 23 25 29 25 161v136l95-4c82-3 97-6 117-26l23-23v-349-349l-46-46-930-6-29 30c-17 16-30 34-30 40 0 7 34 11 95 11 88 0 98 2 120 25 16 15 25 36 25 55s-9 40-25 55c-22 23-32 25-120 25h-95v80 80h55c67 0 105 29 105 80 0 19-9 40-25 55l-24 25h-231-231l-24-25c-33-32-33-78 0-110 22-23 32-25 120-25h95v-80-80h-175c-173 0-176 0-200-25-33-32-33-78 0-110 24-25 27-25 197-25h174l12-45c23-88 85-154 171-183 22-8 112-12 253-12h220l-37-43c-103-119-197-418-211-669-7-115-7-116 19-142 26-25 29-26 164-26h138l16-69c55-226 235-407 464-466 77-20 233-20 310 0 228 59 409 240 463 464l17 71h605 606l13-62c58-281 328-498 621-498 349 0 640 291 640 640 0 237-141 465-350 569-89 43-193 71-271 71h-46l-142 331c-78 183-140 333-139 335 2 1 28-4 58-12 80-21 117-18 145 11l25 24v351 351l-26 26c-24 24-30 25-91 20-130-12-265-105-317-217l-23-49-29 30c-16 17-51 43-79 57-49 26-54 27-208 24-186-3-227 9-300 87-43 46-137 173-137 185 0 3 10 6 23 6s48 12 78 28c61 31 112 91 131 155 7 25 25 53 45 70 79 68 91 152 34 242-17 27-36 65-41 85-13 46-13 100 0 100 6 0 22 11 35 25 30 29 33 82 10 190-61 290-332 508-630 504-38-1-88-5-110-9zm230-165c87-23 168-70 230-136 55-57 108-153 121-216l6-31-153-4c-131-3-161-6-201-25-66-30-133-96-165-162-26-52-28-66-31-210l-4-153-31 6c-63 13-159 66-216 121-66 62-113 143-136 230-88 339 241 668 580 580zm293-619c7-41 28-106 48-147l36-74-24-15c-43-28-68-59-68-85 0-40-26-92-54-110-30-20-127-16-211 8l-50 14-3 175c-2 166-1 176 21 218 35 67 86 90 202 90h91l12-74zm-538-496c132-25 214-88 348-269 101-137 165-199 241-237 31-15 57-29 59-30s-6-20-17-43c-12-22-27-75-33-117-12-74-12-76-38-71-149 30-321 156-424 311-53 80-90 95-140 55-48-38-35-89 52-204l30-39-28-36c-42-54-91-145-110-208l-18-57-337-3-338-2 6 82c9 112 47 272 95 400 135 357 365 522 652 468zm1490-630c0-254 1-252-83-167-54 53-77 104-77 167s23 114 77 168c84 84 83 86 83-168zm-454 63c18-13 41-46 57-83l26-61-45-19c-75-33-165-52-244-54l-75-1-3 29c-8 72 44 166 113 201 42 22 132 16 171-12zm-2346-63v-80h-120-120v80 80h120 120v-80zm1584-184c80-52 154-84 261-111l90-23 112-483c68-295 112-506 112-540 1-68-21-134-56-171l-26-27-17 48c-29 86-99 159-177 186l-38 13-6 279c-5 297-5 297-64 414-58 113-212 233-328 254-21 4-41 14-44 21-12 32 88 201 111 186 6-4 37-24 70-46zm1099-493 185-433-348-490h-138-138l33 68c40 81 56 176 44 252-8 47-203 894-217 941-4 13 9 17 75 23 80 6 230 44 280 71 14 7 29 10 32 7 4-4 90-202 192-439zm-1323 187c118-22 229-99 275-190 37-74 45-138 45-375v-225h-160-160v115c0 179-47 289-158 369-91 67-141 76-417 76h-244l10 32c5 18 9 72 9 120v88h374c209 0 397-4 426-10zm-319-402c50-15 111-67 135-115 16-32 20-70 24-244l5-205 36-72 35-72h-759-759l7 63c17 164 95 400 165 502 47 68 129 124 215 145 52 13 853 12 896-2zm2114-323c256-67 415-329 350-580-48-184-202-326-390-358-197-34-412 76-500 257-19 39-38 86-41 104l-6 32h80 81l24-53c31-69 86-123 156-156 77-36 192-36 266-1 63 31 124 91 156 155 33 68 34 197 2 267-27 60-95 127-156 157-95 46-229 36-311-22-18-12-26-15-21-6 13 22 126 182 143 202 19 22 86 23 167 2zm-1315-243c39-21 87-99 77-125-6-15-27-17-178-17-193 0-231 7-289 58-35 29-70 78-70 97 0 3 96 5 213 5 187 0 217-2 247-18zm1288-89c51-38 67-70 67-133s-16-95-69-134c-43-33-132-29-179 7-20 15-37 32-37 38 0 5 36 9 80 9 73 0 83 3 105 25 33 32 33 78 0 110-22 22-32 25-105 25-44 0-80 4-80 8 0 12 29 37 65 57 39 21 117 15 153-12zm-397-46c-10-9-11-8-5 6 3 10 9 15 12 12s0-11-7-18zm-2460-217c45-106 169-184 289-184s244 78 289 184l22 50h81 81l-7-32c-13-65-66-159-123-219-186-195-500-195-686 0-57 60-110 154-123 219l-6 32h80 81l22-50zm419 41c0-16-51-50-91-63-30-8-48-8-78 0-40 13-91 47-91 63 0 5 57 9 130 9s130-4 130-9z" />
                </g>
            </svg>
            <select :value="selectedDeliveryExecutive || ''"
                @change="$emit('update:selectedDeliveryExecutive', $event.target.value)"
                class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 text-sm focus:outline-none focus:ring-1 focus:ring-gray-500 dark:focus:ring-gray-400 focus:border-transparent cursor-pointer">
                <option value="">Select Delivery Executive</option>
                <option v-for="executive in deliveryExecutives" :key="executive.id" :value="executive.id">
                    {{ executive.name }}
                </option>
            </select>
        </div>

        <!-- Order Header -->
        <div>
            <div class="mt-2 flex items-start justify-between gap-3">
                <div v-if="customer?.id" class="min-w-0 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white truncate">
                            {{ customer.name }}
                        </div>
                        <div v-if="customer.phone" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ customer.phone_code ? '+' + customer.phone_code + ' ' : '' }}{{ customer.phone }}
                        </div>
                    </div>
                    <button type="button" @click="$emit('show-add-customer')"
                        class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-md border border-gray-200 bg-gray-50 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        title="Change Customer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path
                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                            <path fill-rule="evenodd"
                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" />
                        </svg>
                    </button>
                    <button type="button" @click="$emit('remove-customer')"
                        class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-600 transition-colors hover:bg-red-100 hover:text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/40"
                        title="Remove Customer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-x-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14m0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16" />
                            <path
                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                        </svg>
                    </button>
                </div>
                <a v-else href="javascript:;" @click="$emit('show-add-customer')"
                    class="text-sm underline underline-offset-2 dark:text-gray-300">
                    {{ customer?.id ? 'Change Customer' : '+ Add Customer Details' }}
                </a>
            </div>

            <div class="flex justify-between my-2 items-center">
                <div class="font-medium py-2 inline-flex items-center gap-1 dark:text-neutral-200 relative group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-receipt w-6 h-6" viewBox="0 0 16 16">
                        <path
                            d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27m.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0z">
                        </path>
                        <path
                            d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5">
                        </path>
                    </svg>
                    <span :class="{
                        'text-yellow-600 dark:text-yellow-400': !isOnline,
                    }">
                        {{ formattedOrderNumber }}
                    </span>
                    <!-- Offline Warning Tooltip -->
                    <div v-if="!isOnline"
                        class="absolute left-0 top-full mt-1 w-64 p-2 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-xs text-yellow-800 dark:text-yellow-300">
                                <strong>Offline Mode:</strong> This order number
                                is temporary and will change when the
                                application becomes online. The server will
                                assign the final order number after
                                synchronization.
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Table Display (only for dine_in) -->
                <div v-if="orderType === 'Dine In' ||
                    orderType === 'dine_in' ||
                    orderType?.toLowerCase() === 'dine in'
                    " class="inline-flex items-center gap-2 dark:text-gray-300">
                    <template v-if="currentTable">
                        <svg fill="currentColor"
                            class="w-5 h-5 transition duration-75 group-hover:text-gray-900 dark:text-gray-200 dark:group-hover:text-white"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44.999 44.999" xml:space="preserve">
                            <g stroke-width="0" />
                            <g stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="m42.558 23.378 2.406-10.92a1.512 1.512 0 0 0-2.954-.652l-2.145 9.733h-9.647a1.512 1.512 0 0 0 0 3.026h.573l-3.258 7.713a1.51 1.51 0 0 0 1.393 2.102c.59 0 1.15-.348 1.394-.925l2.974-7.038 4.717.001 2.971 7.037a1.512 1.512 0 1 0 2.787-1.177l-3.257-7.713h.573a1.51 1.51 0 0 0 1.473-1.187m-28.35 1.186h.573a1.512 1.512 0 0 0 0-3.026H5.134L2.99 11.806a1.511 1.511 0 1 0-2.954.652l2.406 10.92a1.51 1.51 0 0 0 1.477 1.187h.573L1.234 32.28a1.51 1.51 0 0 0 .805 1.98 1.515 1.515 0 0 0 1.982-.805l2.971-7.037 4.717-.001 2.972 7.038a1.514 1.514 0 0 0 1.982.805 1.51 1.51 0 0 0 .805-1.98z" />
                            <path
                                d="M24.862 31.353h-.852V18.308h8.13a1.513 1.513 0 1 0 0-3.025H12.856a1.514 1.514 0 0 0 0 3.025h8.13v13.045h-.852a1.514 1.514 0 0 0 0 3.027h4.728a1.513 1.513 0 1 0 0-3.027" />
                        </svg>
                        {{ currentTable }}

                        <button type="button"
                            class="inline-flex items-center px-2 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            @click="showTableAssignmentModal = true" title="Change Table">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-gear" viewBox="0 0 16 16">
                                <path
                                    d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0" />
                                <path
                                    d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z" />
                            </svg>
                        </button>
                    </template>
                    <button v-else type="button"
                        class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        @click="showTableAssignmentModal = true">
                        Set Table
                    </button>
                </div>
            </div>

            <!-- Pax and Waiter -->
            <div class="flex justify-between items-center gap-2">
                <div class="py-2 inline-flex items-center gap-1 text-sm dark:text-gray-300">
                    Pax
                    <input type="number" v-model="localPax" @input="$emit('update:pax', localPax)"
                        class="w-14 px-2 py-1 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 text-sm focus:outline-none focus:ring-1 focus:ring-gray-500 dark:focus:ring-gray-400 focus:border-transparent"
                        step="1" min="1" />
                </div>
                <div class="gap-2 inline-flex items-center">
                    <button type="button"
                        class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 relative"
                        @click="$emit('add-note')" title="Add Note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path
                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                            </path>
                            <path fill-rule="evenodd"
                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                            </path>
                        </svg>
                    </button>
                    <div class="inline-flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-200 hidden lg:block" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z">
                            </path>
                        </svg>
                        <span class="text-sm text-gray-600 dark:text-gray-300">Waiter:</span>
                        <select v-if="showWaiterSelect" v-model="localWaiterId"
                            @change="$emit('update:waiterId', localWaiterId)"
                            class="w-36 px-2 py-1 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 text-sm focus:outline-none focus:ring-1 focus:ring-gray-500 dark:focus:ring-gray-400 focus:border-transparent cursor-pointer">
                            <option value="">Select Waiter</option>
                            <option v-for="waiter in availableWaiters" :key="waiter.id" :value="waiter.id">
                                {{ waiter.name }}
                            </option>
                        </select>
                        <span v-else class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ selectedWaiterName || 'Select Waiter' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showOrderStatusPanel" class="p-4 mb-3 bg-white rounded-lg shadow-sm dark:bg-gray-800">
            <div class="flex flex-col space-y-4">
                <div class="flex items-center justify-between text-gray-900 dark:text-white">
                    <h3 class="text-lg font-semibold">Set Order Status</h3>
                    <span class="px-3 py-1 text-sm font-medium rounded-full" :class="orderStatusBadgeClass">
                        {{ currentOrderStatusLabel }}
                    </span>
                </div>

                <div class="relative">
                    <div class="relative flex justify-between">
                        <div v-for="(status, index) in orderStatusFlow" :key="status" class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2" :class="index <= currentOrderStatusIndex
                                ? 'bg-skin-base text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="status === 'placed'" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2" />
                                    <path v-else-if="status === 'preparing'" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M5 12h14M7 8h10M8 16h8" />
                                    <path v-else-if="status === 'out_for_delivery' || status === 'ready_for_pickup'"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    <path v-else-if="status === 'delivered' || status === 'served'" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 text-center">
                                {{ orderStatusLabel(status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="canMoveToNextOrderStatus || canCancelOrder" class="flex justify-end items-center mt-2 gap-2">
                    <button v-if="canCancelOrder" type="button" @click="$emit('update:orderStatus', 'cancelled')"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-red-600 border border-red-700 rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <span>Cancel Order</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <button v-if="canMoveToNextOrderStatus" type="button" @click="$emit('update:orderStatus', nextOrderStatus)"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <span>Move to {{ orderStatusLabel(nextOrderStatus) }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="props?.order"
            class="flex justify-between p-2 text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-700">
            <div>KOT #{{ props.order }}</div>
        </div>

        <!-- Cart Items Table -->
        <div class="flex flex-col rounded overflow-visible">
            <table class="flex-1 min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 rtl:text-right ltr:text-left">
                            Item Name
                        </th>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-center text-gray-500 uppercase dark:text-gray-400">
                            Qty
                        </th>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400 hidden lg:table-cell">
                            Price
                        </th>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">
                            Amount
                        </th>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    <tr v-if="cartItems.length === 0" class="hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="p-8 text-center" colspan="5">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <svg class="w-12 h-12 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <div class="text-gray-500 dark:text-gray-400 text-base">
                                    No record found
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="item in cartItems" :key="item.line_key || item.id"
                        class="hover:bg-gray-100 dark:hover:bg-gray-700">
                        <!-- Item Name, Note, and Add Note UI -->
                        <td class="flex flex-col p-2 lg:min-w-20 relative">
                            <div class="text-xs text-gray-900 dark:text-white inline-flex items-center lg:table-cell">
                                {{ item.name }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-white inline-flex items-center">
                                <!-- Optionally show price/unit or item meta data here -->
                            </div>
                            <!-- Special Instructions (Note) UI for each cart item -->
                            <div class="inline-flex items-center relative group" v-cloak>
                                <template v-if="item.note &&
                                    !item._showNoteInput &&
                                    !item._showNotePreview
                                    ">
                                    <div class="flex items-center gap-2 cursor-pointer text-skin-base text-xs hover:text-skin-base/80 transition-all duration-200"
                                        @click="() => {
                                            item._showNotePreview = true;
                                        }
                                            " title="Special Instructions">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 8h10M7 12h4m1 8-4-4H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3z">
                                            </path>
                                        </svg>
                                        <span class="truncate max-w-[70px] md:max-w-64 lg:max-w-[70px]">{{ item.note
                                        }}</span>
                                    </div>
                                </template>

                                <template v-else-if="!item.note &&
                                    !item._showNoteInput &&
                                    !item._showNotePreview
                                    ">
                                    <button @click="() => {
                                        item._showNoteInput = true;
                                        item._activeNote =
                                            item.note || '';
                                    }
                                        "
                                        class="inline-flex items-center gap-1 text-xs pt-1 text-gray-500 hover:text-skin-base transition-colors duration-200"
                                        title="Add Note">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3.5 h-3.5"
                                            fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Add Note
                                    </button>
                                </template>

                                <!-- Note Preview Modal -->
                                <div v-if="item._showNotePreview" class="absolute top-0 left-0 z-10"
                                    @click.away="item._showNotePreview = false">
                                    <div
                                        class="bg-white dark:bg-gray-700 rounded-md shadow-md border border-gray-300 dark:border-gray-600 p-3 w-64 md:w-96">
                                        <div class="text-sm dark:text-white mb-2 break-all">
                                            {{ item.note }}
                                        </div>
                                        <div class="flex justify-end gap-2 dark:text-white">
                                            <button @click="() => {
                                                item._showNotePreview = false;
                                                item._showNoteInput = true;
                                                item._activeNote =
                                                    item.note || '';
                                            }
                                                "
                                                class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 rounded transition-colors duration-200">
                                                <span class="flex items-center gap-x-1">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" stroke="currentColor"
                                                        fill="none">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                        </path>
                                                    </svg>
                                                    Update
                                                </span>
                                            </button>
                                            <button @click="() => {
                                                $emit('add-note', {
                                                    id: item.id,
                                                    note: '',
                                                });
                                                item._showNotePreview = false;
                                            }
                                                "
                                                class="text-xs px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-700 dark:hover:bg-red-600 text-red-500 dark:text-red-300 rounded transition-colors duration-200"
                                                title="Delete">
                                                <span class="flex items-center gap-x-1">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" stroke="currentColor"
                                                        fill="none">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    Delete
                                                </span>
                                            </button>
                                            <button @click="
                                                item._showNotePreview = false
                                                "
                                                class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 rounded transition-colors duration-200">
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Note Input Modal -->
                                <div v-if="item._showNoteInput" class="fixed inset-0 z-40"
                                    @click="item._showNoteInput = false"></div>
                                <div v-if="item._showNoteInput" class="absolute top-0 left-full ml-2 z-50 min-w-[280px]"
                                    @click.stop>
                                    <div class="flex items-center bg-white dark:bg-gray-700 rounded-md shadow-2xl border-2 border-gray-300 dark:border-gray-600 overflow-hidden"
                                        @click.stop>
                                        <input type="text" v-model="item._activeNote"
                                            class="w-64 md:w-80 p-2 border-none text-base focus:outline-none focus:ring-2 focus:ring-skin-base dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                                            placeholder="Special Instructions? (e.g., no onions, extra spicy)"
                                            @keydown.enter="() => {
                                                $emit('add-note', {
                                                    id: item.id,
                                                    note: item._activeNote,
                                                });
                                                item._showNoteInput = false;
                                            }
                                                " @keydown.escape="
        item._showNoteInput = false
        " autofocus :ref="(el) => {
        if (
            el &&
            item._showNoteInput
        )
            el.focus();
    }
        " />
                                        <div class="flex items-center gap-1 pr-2">
                                            <button @click.stop="() => {
                                                if (
                                                    item._activeNote &&
                                                    item._activeNote.trim()
                                                ) {
                                                    $emit('add-note', {
                                                        id: item.id,
                                                        note: item._activeNote.trim(),
                                                    });
                                                }
                                                item._showNoteInput = false;
                                            }
                                                "
                                                class="p-1.5 text-white rounded-md bg-skin-base hover:bg-skin-base/90 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-skin-base focus:ring-offset-2"
                                                title="Save" type="button">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="m5 13 4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button @click.stop="() => {
                                                item._showNoteInput = false;
                                                item._activeNote =
                                                    item.note || '';
                                            }
                                                "
                                                class="p-1.5 text-gray-500 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                                title="Cancel" type="button">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18 18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Quantity Control -->
                        <td class="p-2 text-base text-gray-900 whitespace-nowrap text-center">
                            <div class="relative flex items-center max-w-[8rem] mx-auto">
                                <button type="button" @click="$emit('decrease-quantity', item.line_key || item.id)"
                                    class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-s-md p-3 h-8 relative">
                                    <svg class="w-2 h-2 text-gray-900 dark:text-white" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="M1 1h16"></path>
                                    </svg>
                                </button>
                                <input type="text" v-model.lazy="item.quantity" @change="
                                    $emit('update-quantity', {
                                        line_key: item.line_key || item.id,
                                        id: item.id,
                                        quantity: item.quantity,
                                        variant_id: item.variant_id || 0,
                                        modifier_id: item.modifier_id || 0,
                                    })
                                    "
                                    class="min-w-10 border-b border-t bg-white border-x-0 border-gray-300 h-8 text-center text-gray-900 text-sm block w-full py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    min="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                <button type="button" @click="$emit('increase-quantity', item.line_key || item.id)"
                                    class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-e-md p-3 h-8 relative">
                                    <svg class="w-2 h-2 text-gray-900 dark:text-white" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="M9 1v16M1 9h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <td
                            class="p-2 text-xs font-medium text-gray-700 whitespace-nowrap dark:text-white text-right hidden lg:table-cell">
                            {{ currencySymbol }} {{ formatPrice(item.price) }}
                        </td>
                        <td class="p-2 text-xs font-medium text-gray-900 whitespace-nowrap dark:text-white text-right">
                            {{ currencySymbol }}
                            {{ formatPrice(item.price * item.quantity) }}
                        </td>
                        <td class="p-2 whitespace-nowrap text-right">
                            <button
                                class="rounded text-gray-800 dark:text-gray-400 border dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-900/20 p-2 relative"
                                @click="$emit('remove-item', item.line_key || item.id)">
                                <svg class="w-4 h-4 text-gray-700 dark:text-gray-200" fill="currentColor"
                                    viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Order Summary -->
        <div class="lg:min-w-20">
            <div class="h-auto p-4 mt-3 select-none text-center bg-gray-50 rounded space-y-4 dark:bg-gray-700"
                v-if="cartItems.length > 0">
                <div class="text-left">
                    <button
                        class="text-left inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        @click="showDiscountModal = true">
                        <svg class="h-5 w-5 text-current me-1" width="24" height="24" viewBox="0 0 16 16"
                            xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="1.5">
                            <path d="m7.25 14.25-5.5-5.5 7-7h5.5v5.5z" />
                            <circle cx="11" cy="5" r=".5" fill="#000" />
                        </svg>
                        Add Discount
                    </button>
                </div>

                <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                    <div>Total Items</div>
                    <div>
                        {{ totalItems }}
                    </div>
                </div>
                <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                    <div>Sub Total</div>
                    <div>{{ currencySymbol }}{{ formatPrice(subTotal) }}</div>
                </div>

                <div v-if="discountAmount && discountAmount > 0">
                    <div class="flex justify-between text-green-500 text-sm dark:text-green-400">
                        <div class="inline-flex items-center gap-x-1">
                            Discount
                            <span v-if="discountType === 'percent'">
                                ({{ discountValue }}%)
                            </span>
                            <span class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                                @click="$emit('remove-discount')">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                        <div>
                            -{{ currencySymbol
                            }}{{ formatPrice(discountAmount) }}
                        </div>
                    </div>
                </div>

                <div v-if="selectedOrderTypeSlug === 'delivery'">
                    <div class="flex justify-between items-center text-gray-500 text-sm dark:text-neutral-400">
                        <div>
                            Delivery Fee
                            <span v-if="deliveryFee === 0" class="text-xs text-gray-400">
                                (Free Delivery)
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <input type="number" step="1" min="0"
                                    class="w-16 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-gray-500 dark:focus:border-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 rounded-md shadow-sm"
                                    :value="deliveryFee" @input="
                                        $emit(
                                            'update:deliveryFee',
                                            parseFloat($event.target.value) || 0
                                        )
                                        " />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="extraCharges && extraCharges.length > 0">
                    <div v-for="charge in extraCharges" :key="charge.id">
                        <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                            <div class="inline-flex items-center gap-x-1">
                                {{ charge.name || charge.charge_name }}
                                <span v-if="charge.charge_type === 'percent'">
                                    ({{ charge.value || charge.charge_value }}%)
                                </span>
                                <span class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer" @click="
                                    $emit('remove-extra-charge', charge.id)
                                    ">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>

                            <div>
                                {{ currencySymbol }}{{
                                    charge.charge_type === "percent"
                                    ? ((subTotal - discountAmount) *
                                        charge.charge_value) /
                                    100
                                    : charge.charge_value
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="taxes.length > 0">
                    <div v-for="tax in taxes" :key="tax.id">
                        <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                            <div>
                                {{ tax.tax_name }} ({{
                                    tax.rate || tax.tax_percent
                                }}%)
                            </div>
                            <div>
                                {{ currencySymbol }}
                                {{ formatPrice(tax.amount) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="totalTaxAmount > 0"
                        class="flex justify-between text-gray-500 text-sm dark:text-neutral-400 mt-3">
                        <div>
                            Total Tax
                            <span v-if="isInclusive" class="text-xs text-gray-400">
                                (Tax Inclusive)
                            </span>
                            <span v-else class="text-xs text-gray-400">
                                (Tax Exclusive)
                            </span>
                        </div>
                        <div>
                            {{ currencySymbol }}
                            {{ formatPrice(totalTaxAmount) }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-between font-medium dark:text-neutral-300">
                    <div>Total</div>
                    <div>{{ currencySymbol }} {{ formatPrice(total) }}</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="h-auto pb-4 pt-3 select-none text-center w-full mb-16 md:mb-0">
                <div class="flex gap-3">
                    <button class="rounded bg-gray-700 text-white w-full p-2 relative" @click="handleSaveOrder('kot')"
                        :disabled="isSavingKot" :class="{ 'opacity-50 cursor-not-allowed': isSavingKot }">
                        <span v-if="!isSavingKot">KOT</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-1 h-4 w-4 inline-flex text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            KOT
                        </span>
                    </button>
                    <button class="rounded bg-gray-700 text-white w-full p-2 relative"
                        @click="handleSaveOrder('kot', 'print')" :disabled="isSavingKotPrint"
                        :class="{ 'opacity-50 cursor-not-allowed': isSavingKotPrint }">
                        <span v-if="!isSavingKotPrint">KOT &amp; Print</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            KOT &amp; Print
                        </span>
                    </button>
                    <button class="rounded bg-gray-700 text-white w-full p-2 relative"
                        @click="handleSaveOrder('kot', 'bill', 'payment')" :disabled="isSavingKotBillPayment"
                        :class="{ 'opacity-50 cursor-not-allowed': isSavingKotBillPayment }">
                        <span v-if="!isSavingKotBillPayment">KOT, Bill &amp; Payment</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin inline-flex -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            KOT, Bill &amp; Payment
                        </span>
                    </button>
                </div>
                <div class="flex gap-3 mt-3">
                    <button class="rounded bg-skin-base text-white w-full p-2 relative" @click="handleSaveOrder('bill')"
                        :disabled="isSavingBill" :class="{ 'opacity-50 cursor-not-allowed': isSavingBill }">
                        <span v-if="!isSavingBill">BILL</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin inline-flex items-center -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            BILL
                        </span>
                    </button>
                    <button class="rounded bg-green-500 text-white w-full p-2 relative"
                        @click="handleSaveOrder('bill', 'payment')" :disabled="isSavingBillPayment"
                        :class="{ 'opacity-50 cursor-not-allowed': isSavingBillPayment }">
                        <span v-if="!isSavingBillPayment">Bill &amp; Payment</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-flex items-center"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Bill &amp; Payment
                        </span>
                    </button>
                    <button class="rounded bg-blue-500 text-white w-full p-2 relative"
                        @click="handleSaveOrder('bill', 'print')" :disabled="isSavingBillPrint"
                        :class="{ 'opacity-50 cursor-not-allowed': isSavingBillPrint }">
                        <span v-if="!isSavingBillPrint">Bill &amp; Print</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Bill &amp; Print
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Discount Modal -->
        <DiscountModal :show="showDiscountModal" @close="showDiscountModal = false" @save="handleApplyDiscount" />

        <!-- Table Assignment Modal -->
        <TableAssignmentModal :show="showTableAssignmentModal" @close="showTableAssignmentModal = false"
            @select="handleSelectTable" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import DiscountModal from "./DiscountModal.vue";
import TableAssignmentModal from "./TableAssignmentModal.vue";
import { showPosAlert } from "../../utils/posAlerts.js";

const props = defineProps({
    orderType: {
        type: String,
        default: "Dine In",
    },
    orderNumber: {
        type: [String, Number],
        default: "",
    },
    currentTable: {
        type: String,
        default: "",
    },
    customer: {
        type: Object,
        default: () => null,
    },
    pax: {
        type: Number,
        default: 1,
    },
    waiterId: {
        type: [String, Number],
        default: "",
    },
    canEditWaiter: {
        type: Boolean,
        default: true,
    },
    waiters: {
        type: Array,
        default: () => [],
    },
    cartItems: {
        type: Array,
        default: () => [],
    },
    taxes: {
        type: Array,
        default: () => [],
    },
    savingAction: {
        type: String,
        default: null,
    },
    discountAmount: {
        type: Number,
        default: 0,
    },
    discountType: {
        type: String,
        default: "",
    },
    discountValue: {
        type: Number,
        default: 0,
    },
    deliveryFee: {
        type: Number,
        default: 0,
    },
    extraCharges: {
        type: Array,
        default: () => [],
    },
    isInclusive: {
        type: Boolean,
        default: false,
    },
    totalTaxAmount: {
        type: Number,
        default: 0,
    },
    currencySymbol: {
        type: String,
        default: "$",
    },
    isOnline: {
        type: Boolean,
        default: true,
    },
    order: {
        type: Object,
        default: () => null,
    },
    orderTypes: {
        type: Array,
        default: () => [],
    },
    deliveryPlatforms: {
        type: Array,
        default: () => [],
    },
    selectedDeliveryApp: {
        type: [String, Number],
        default: "default",
    },
    setAsDefaultOrderType: {
        type: Boolean,
        default: false,
    },
    orderStatus: {
        type: String,
        default: "",
    },
    deliveryExecutives: {
        type: Array,
        default: () => [],
    },
    selectedDeliveryExecutive: {
        type: [String, Number],
        default: "",
    },
    currentUser: {
        type: Object,
        default: () => null,
    },
});

const emit = defineEmits([
    "change-order-type",
    "update:orderType",
    "show-add-customer",
    "remove-customer",
    "assign-table",
    "select-table",
    "update:pax",
    "update:waiterId",
    "add-note",
    "update-quantity",
    "increase-quantity",
    "decrease-quantity",
    "remove-item",
    "save-order",
    "remove-discount",
    "remove-extra-charge",
    "update:deliveryFee",
    "update:extraCharges",
    "apply-discount",
    "update:selectedDeliveryApp",
    "update:setAsDefaultOrderType",
    "update:orderStatus",
    "update:selectedDeliveryExecutive",
]);

const localPax = ref(props.pax);
const localWaiterId = ref(props.waiterId);
const showDiscountModal = ref(false);
const showOrderTypeDropdown = ref(false);
const showTableAssignmentModal = ref(false);
const formattedOrderNumber = ref(props.orderNumber || "");
const availableOrderTypes = ref([]);
const availableDeliveryPlatforms = ref([]);
const localOrderTypeId = ref("");
const localSelectedDeliveryApp = ref("default");
const localSetAsDefaultOrderType = ref(false);
const loadingOrderTypes = ref(false);
const savingOrderPreferences = ref(false);
const fallbackWaiters = ref([]);

watch(
    () => props.pax,
    (newVal) => {
        localPax.value = newVal;
    }
);

watch(
    () => props.waiterId,
    (newVal) => {
        localWaiterId.value = newVal;
    }
);

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

const slugToDisplayType = (slug) => {
    if (slug === "dine_in") return "Dine In";
    if (slug === "pickup") return "Pickup";
    if (slug === "delivery") return "Delivery";
    return slug;
};

const selectedOrderTypeSlug = computed(() => {
    const selectedId = Number(localOrderTypeId.value);
    const selectedType = availableOrderTypes.value.find(
        (type) => Number(type.id) === selectedId
    );

    return selectedType
        ? normalizeOrderTypeSlug(selectedType.slug)
        : normalizeOrderTypeSlug(props.orderType);
});

const isCurrentUserWaiter = computed(() => {
    return !!props.currentUser?.is_waiter;
});

const showWaiterSelect = computed(() => {
    return !!props.canEditWaiter;
});

const orderStatusFlow = computed(() => {
    const slug = normalizeOrderTypeSlug(props.orderType);

    if (slug === "delivery") {
        return ["placed", "confirmed", "preparing", "food_ready", "out_for_delivery", "delivered"];
    }

    if (slug === "pickup") {
        return ["placed", "confirmed", "preparing", "food_ready", "ready_for_pickup", "delivered"];
    }

    return ["placed", "confirmed", "preparing", "food_ready", "served"];
});

const orderStatusLabel = (status) => {
    const labels = {
        placed: "Order Placed",
        confirmed: "Order Confirmed",
        preparing: "Order Preparing",
        food_ready: "Food is Ready",
        ready_for_pickup: "Order is Ready for Pickup",
        out_for_delivery: "Order is Out for Delivery",
        served: "Order Served",
        delivered: "Delivered",
        cancelled: "Order Cancelled",
    };

    return labels[status] || status;
};

const currentOrderStatus = computed(() => String(props.orderStatus || "").toLowerCase());

const currentOrderStatusIndex = computed(() => {
    const index = orderStatusFlow.value.indexOf(currentOrderStatus.value);
    return index >= 0 ? index : 0;
});

const currentOrderStatusLabel = computed(() => {
    return orderStatusLabel(currentOrderStatus.value || orderStatusFlow.value[0]);
});

const nextOrderStatus = computed(() => {
    const nextIndex = Math.min(currentOrderStatusIndex.value + 1, orderStatusFlow.value.length - 1);
    return orderStatusFlow.value[nextIndex] || null;
});

const canMoveToNextOrderStatus = computed(() => {
    if (!props.canEditWaiter || !currentOrderStatus.value) {
        return false;
    }

    if (currentOrderStatus.value === "cancelled") {
        return false;
    }

    return currentOrderStatusIndex.value < orderStatusFlow.value.length - 1;
});

const canCancelOrder = computed(() => {
    return !!props.canEditWaiter && currentOrderStatus.value === "placed";
});

const showOrderStatusPanel = computed(() => {
    return !!props.order && !!currentOrderStatus.value;
});

const orderStatusBadgeClass = computed(() => {
    if (["delivered", "served"].includes(currentOrderStatus.value)) {
        return "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300";
    }

    if (currentOrderStatus.value === "cancelled") {
        return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
    }

    if (currentOrderStatus.value === "placed") {
        return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
    }

    return "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300";
});

const availableWaiters = computed(() => {
    const source = Array.isArray(props.waiters) && props.waiters.length > 0
        ? props.waiters
        : fallbackWaiters.value;

    if (!Array.isArray(source)) {
        return [];
    }

    return source
        .map((waiter) => ({
            id: waiter?.id ?? waiter?.value ?? waiter?.user_id ?? waiter?.waiter_id ?? "",
            name: String(
                waiter?.name ?? waiter?.full_name ?? waiter?.display_name ?? waiter?.label ?? ""
            ).trim(),
        }))
        .filter((waiter) => waiter.id !== "" && waiter.name !== "");
});

const selectedWaiterName = computed(() => {
    const selectedId = Number(localWaiterId.value || 0);
    if (!selectedId) {
        return "";
    }

    const selected = availableWaiters.value.find(
        (waiter) => Number(waiter.id) === selectedId
    );

    if (selected?.name) {
        return selected.name;
    }

    if (props.currentUser?.id && Number(props.currentUser.id) === selectedId) {
        return String(props.currentUser.name || "");
    }

    return "";
});

const syncLocalOrderTypeId = () => {
    const currentSlug = normalizeOrderTypeSlug(props.orderType);
    const matchedType = availableOrderTypes.value.find(
        (type) => normalizeOrderTypeSlug(type.slug) === currentSlug
    );

    localOrderTypeId.value = matchedType ? String(matchedType.id) : "";
};

const fetchOrderTypes = async () => {
    if (loadingOrderTypes.value || availableOrderTypes.value.length > 0) return;

    loadingOrderTypes.value = true;
    try {
        const response = await axios.get("/api/pos/order-types");
        if (Array.isArray(response.data)) {
            availableOrderTypes.value = response.data;
            syncLocalOrderTypeId();
        }
    } catch (error) {
        console.error("Error fetching order types:", error);
    } finally {
        loadingOrderTypes.value = false;
    }
};

const fetchWaiters = async () => {
    if (availableWaiters.value.length > 0) {
        return;
    }

    try {
        const response = await axios.get("/api/pos/waiters");
        if (Array.isArray(response.data)) {
            fallbackWaiters.value = response.data;
        }
    } catch (error) {
        console.error("Error fetching waiters:", error);
    }
};

const persistOrderPreferences = async () => {
    if (savingOrderPreferences.value) {
        return;
    }

    const selectedId = Number(localOrderTypeId.value);
    if (!selectedId) {
        return;
    }

    savingOrderPreferences.value = true;
    try {
        await axios.post("/api/pos/order-preferences", {
            order_type_id: selectedId,
            set_as_default_order_type: !!localSetAsDefaultOrderType.value,
            selected_delivery_app:
                selectedOrderTypeSlug.value === "delivery"
                    ? localSelectedDeliveryApp.value || "default"
                    : null,
        });
    } catch (error) {
        console.error("Error saving POS order preferences:", error);
    } finally {
        savingOrderPreferences.value = false;
    }
};

watch(
    () => props.orderTypes,
    (newVal) => {
        if (Array.isArray(newVal) && newVal.length > 0) {
            availableOrderTypes.value = newVal;
            syncLocalOrderTypeId();
        }
    },
    { immediate: true }
);

watch(
    () => props.deliveryPlatforms,
    (newVal) => {
        if (Array.isArray(newVal) && newVal.length > 0) {
            availableDeliveryPlatforms.value = newVal;
        }
    },
    { immediate: true }
);

watch(
    () => props.selectedDeliveryApp,
    (newVal) => {
        localSelectedDeliveryApp.value = newVal ? String(newVal) : "default";
    },
    { immediate: true }
);

watch(
    () => props.setAsDefaultOrderType,
    (newVal) => {
        localSetAsDefaultOrderType.value = !!newVal;
    },
    { immediate: true }
);

watch(
    () => props.orderType,
    () => {
        syncLocalOrderTypeId();
    },
    { immediate: true }
);

watch(
    () => showOrderTypeDropdown.value,
    (isOpen) => {
        if (isOpen && availableOrderTypes.value.length === 0) {
            fetchOrderTypes();
        }
    }
);

const totalItems = computed(() => {
    return props.cartItems.reduce((sum, item) => sum + item.quantity, 0);
});

const subTotal = computed(() => {
    return props.cartItems.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
    );
});

const total = computed(() => {
    let calculatedTotal = subTotal.value;

    // Subtract discount
    if (props.discountAmount && props.discountAmount > 0) {
        calculatedTotal -= props.discountAmount;
    }

    // Add delivery fee
    if (props.deliveryFee && props.deliveryFee > 0) {
        calculatedTotal += props.deliveryFee;
    }

    // Add extra charges
    if (props.extraCharges && props.extraCharges.length > 0) {
        const extraChargesTotal = props.extraCharges.reduce(
            (sum, charge) => sum + (charge.amount || 0),
            0
        );
        calculatedTotal += extraChargesTotal;
    }

    // Add taxes only if not inclusive
    if (!props.isInclusive) {
        const taxTotal = props.taxes.reduce(
            (sum, tax) => sum + (tax.amount || 0),
            0
        );
        calculatedTotal += taxTotal;
    }

    return Math.max(0, calculatedTotal);
});

// Computed properties to check if each button is currently saving
const isSavingKot = computed(() => props.savingAction === 'kot');
const isSavingKotPrint = computed(() => props.savingAction === 'kot_print');
const isSavingKotBillPayment = computed(() => props.savingAction === 'kot_bill_payment');
const isSavingBill = computed(() => props.savingAction === 'bill');
const isSavingBillPayment = computed(() => props.savingAction === 'bill_payment');
const isSavingBillPrint = computed(() => props.savingAction === 'bill_print');

// Check if ANY action is being saved (for overall disable state)
const anySaving = computed(() => props.savingAction !== null);

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2);
};

// Fetch extra charges based on order type
const fetchExtraCharges = async (orderTypeValue) => {
    try {
        // Normalize order type for API (e.g., "Dine In" -> "dine_in", "Delivery" -> "delivery")
        let normalizedOrderType = orderTypeValue;
        if (orderTypeValue.includes(" ")) {
            normalizedOrderType = orderTypeValue
                .toLowerCase()
                .replace(/\s+/g, "_");
        } else {
            normalizedOrderType = orderTypeValue.toLowerCase();
        }

        // Handle display formats
        if (normalizedOrderType === "dine in") {
            normalizedOrderType = "dine_in";
        }

        const response = await axios.get(
            `/api/pos/extra-charges/${normalizedOrderType}`
        );

        if (response.data) {
            emit("update:extraCharges", response.data);
        }
    } catch (error) {
        console.error("Error fetching extra charges:", error);
        // Emit empty array on error
        emit("update:extraCharges", []);
    }
};

// Watch for order type changes and fetch extra charges
watch(
    () => props.orderType,
    (newOrderType) => {
        if (newOrderType) {
            fetchExtraCharges(newOrderType);
        }
    },
    { immediate: false }
);

// Fetch formatted order number
const fetchOrderNumber = async () => {
    try {
        const response = await axios.get("/api/pos/get-order-number");
        // API returns array format: [order_number, formatted_order_number]
        if (Array.isArray(response.data) && response.data.length >= 2) {
            formattedOrderNumber.value =
                response.data[1] || response.data[0] || "";
        } else if (response.data?.formatted_order_number) {
            formattedOrderNumber.value = response.data.formatted_order_number;
        } else if (response.data?.order_number) {
            formattedOrderNumber.value = response.data.order_number;
        } else {
            formattedOrderNumber.value = props.orderNumber || "";
        }
        console.log("Fetched order number:", formattedOrderNumber.value);
    } catch (error) {
        console.error("Error fetching order number:", error);
        formattedOrderNumber.value = props.orderNumber || "";
    }
};

// Watch for orderNumber prop changes
watch(
    () => props.orderNumber,
    (newVal) => {
        if (!newVal) {
            // If orderNumber is empty, fetch a new one
            fetchOrderNumber();
        } else {
            formattedOrderNumber.value = newVal;
        }
    },
    { immediate: true }
);

// Fetch extra charges on mount
onMounted(() => {
    if (props.orderType) {
        fetchExtraCharges(props.orderType);
    }
    fetchWaiters();
    // Fetch order number if not provided
    if (!props.orderNumber) {
        fetchOrderNumber();
    }
});

// Handle discount application
const handleApplyDiscount = (discountData, done) => {
    try {
        emit("apply-discount", discountData);
        showDiscountModal.value = false;
        if (typeof done === "function") {
            done();
        }
    } catch (error) {
        if (typeof done === "function") {
            done(error);
        }
    }
};

// Handle order type selection from dropdown
const handleOrderTypeChange = () => {
    const selectedId = Number(localOrderTypeId.value);
    const selectedType = availableOrderTypes.value.find(
        (type) => Number(type.id) === selectedId
    );

    if (!selectedType) {
        return;
    }

    if (normalizeOrderTypeSlug(selectedType.slug) !== "delivery") {
        localSelectedDeliveryApp.value = "default";
    }

    emit("update:orderType", slugToDisplayType(selectedType.slug));
    emit("update:selectedDeliveryApp", localSelectedDeliveryApp.value);
    emit("update:setAsDefaultOrderType", !!localSetAsDefaultOrderType.value);
    persistOrderPreferences();
    showOrderTypeDropdown.value = false;
};

const handleSetDefaultOrderType = () => {
    emit("update:setAsDefaultOrderType", !!localSetAsDefaultOrderType.value);
    persistOrderPreferences();
};

const handleSelectDeliveryPlatform = () => {
    emit("update:selectedDeliveryApp", localSelectedDeliveryApp.value || "default");
    persistOrderPreferences();
};

// Handle table selection
const handleSelectTable = (table) => {
    emit("select-table", table);
    showTableAssignmentModal.value = false;
};

// Handle save order with validation
const handleSaveOrder = (...actions) => {
    // Validate that there are items in the cart
    if (!props.cartItems || props.cartItems.length === 0) {
        showPosAlert("error", "You need to add items to the order.");
        return;
    }

    // Emit the save-order event with actions
    emit("save-order", ...actions);
};
</script>

<style scoped></style>
