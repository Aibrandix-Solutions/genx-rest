<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6"
        @keydown.esc.prevent="emitClose"
    >
        <div class="absolute inset-0 bg-gray-900/50" @click="emitClose"></div>

        <div
            class="relative w-full max-h-[92vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-xl shadow-2xl transition-all duration-300"
            :class="isSplit && splitType === 'items' ? 'max-w-5xl' : 'max-w-3xl'"
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
                    <div v-if="saving || loadingOrder" class="text-xs text-amber-600 dark:text-amber-400">
                        {{ loadingOrder ? 'Loading order items…' : 'Saving order…' }}
                    </div>
                </div>
            </div>

            <div class="p-5 space-y-4">
                <!-- Tab Selector -->
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="flex-1 py-2 px-3 text-sm rounded-lg border font-medium transition-all duration-200"
                        :class="!isSplit
                            ? 'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200'
                            : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        @click="isSplit = false"
                    >
                        Full Payment
                    </button>
                    <button
                        type="button"
                        class="flex-1 py-2 px-3 text-sm rounded-lg border font-medium transition-all duration-200"
                        :class="isSplit
                            ? 'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200'
                            : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        :disabled="saving || !orderId"
                        @click="isSplit = true"
                    >
                        Split Bill
                    </button>
                </div>

                <!-- Full Payment Section -->
                <div v-if="!isSplit" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            <button
                                v-for="method in methods"
                                :key="method.id"
                                type="button"
                                class="p-2 text-center border rounded-lg text-sm transition-all duration-200"
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

                <!-- Split Bill Section -->
                <div v-else class="space-y-4">
                    <!-- Choose Split Type -->
                    <div v-if="splitType === null" class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-4">
                        <button
                            type="button"
                            class="p-4 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600 dark:text-white group transition-all duration-200"
                            @click="selectSplitType('equal')"
                        >
                            <svg class="w-8 h-8 mx-auto mb-2 text-blue-500 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="block font-medium">Equal Split</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Split bill equally</span>
                        </button>

                        <button
                            type="button"
                            class="p-4 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600 dark:text-white group transition-all duration-200"
                            @click="selectSplitType('custom')"
                        >
                            <svg class="w-8 h-8 mx-auto mb-2 text-green-500 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span class="block font-medium">Custom Split</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Split by custom amounts</span>
                        </button>

                        <button
                            type="button"
                            class="p-4 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600 dark:text-white group transition-all duration-200"
                            @click="selectSplitType('items')"
                        >
                            <svg class="w-8 h-8 mx-auto mb-2 text-purple-500 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="block font-medium">Split by Items</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Split by selecting dishes</span>
                        </button>
                    </div>

                    <!-- Split Config & Details -->
                    <div v-else>
                        <div class="flex items-center justify-between border-b pb-2 mb-4 dark:border-gray-700">
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                Split Mode:
                                <span class="capitalize font-bold text-skin-base">
                                    {{ splitType === 'equal' ? 'Equal Split' : splitType === 'custom' ? 'Custom Split' : 'Split by Items' }}
                                </span>
                            </div>
                            <button type="button" class="text-xs text-blue-500 hover:underline" @click="splitType = null">
                                (Change Method)
                            </button>
                        </div>

                        <!-- 1. Equal Split View -->
                        <div v-if="splitType === 'equal'" class="space-y-4">
                            <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Number of Splits</span>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="w-8 h-8 flex items-center justify-center border rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 disabled:opacity-50"
                                        :disabled="numberOfSplits <= 2"
                                        @click="decrementEqualSplits"
                                    >
                                        -
                                    </button>
                                    <span class="text-lg font-semibold w-8 text-center text-gray-900 dark:text-white">{{ numberOfSplits }}</span>
                                    <button
                                        type="button"
                                        class="w-8 h-8 flex items-center justify-center border rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100"
                                        @click="incrementEqualSplits"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto pr-1">
                                <div
                                    v-for="(split, index) in equalSplits"
                                    :key="index"
                                    class="p-3 border dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-between gap-3 shadow-sm hover:shadow transition-shadow"
                                >
                                    <div class="min-w-0">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Split {{ index + 1 }}</div>
                                        <div class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ currencySymbol }}{{ formatMoney(equalSplitAmount) }}
                                        </div>
                                    </div>
                                    <select
                                        v-model="split.payment_method"
                                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500 w-32"
                                    >
                                        <option v-for="method in splitMethods" :key="method.id" :value="method.id">
                                            {{ method.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Custom Split View -->
                        <div v-if="splitType === 'custom'" class="space-y-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enter custom split amounts:</span>
                                <button
                                    type="button"
                                    class="py-1 px-3 text-xs border rounded-lg bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="addCustomSplit"
                                >
                                    + Add Split
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto pr-1">
                                <div
                                    v-for="(split, index) in customSplits"
                                    :key="index"
                                    class="p-3 border dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 flex flex-col gap-2 relative shadow-sm hover:shadow"
                                >
                                    <button
                                        v-if="customSplits.length > 2"
                                        type="button"
                                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500"
                                        @click="removeCustomSplit(index)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">Split {{ index + 1 }}</div>

                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-500 text-sm">
                                                {{ currencySymbol }}
                                            </div>
                                            <input
                                                v-model="split.amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                class="w-full pl-7 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                            />
                                        </div>
                                        <select
                                            v-model="split.payment_method"
                                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm w-32 focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="method in splitMethods" :key="method.id" :value="method.id">
                                                {{ method.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm space-y-1">
                                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                                    <span>Split Total</span>
                                    <span class="font-semibold">{{ currencySymbol }}{{ formatMoney(customSplitTotal) }}</span>
                                </div>
                                <div v-if="customRemainingDue > 0" class="flex justify-between text-red-600 dark:text-red-400">
                                    <span>Remaining Due</span>
                                    <span class="font-bold">{{ currencySymbol }}{{ formatMoney(customRemainingDue) }}</span>
                                </div>
                                <div v-if="customReturnAmount > 0" class="flex justify-between text-green-600 dark:text-green-400">
                                    <span>Change Return</span>
                                    <span class="font-bold">{{ currencySymbol }}{{ formatMoney(customReturnAmount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Split by Items View -->
                        <div v-if="splitType === 'items'" class="space-y-4">
                            <!-- Split navigation tabs -->
                            <div class="flex items-center justify-between border-b pb-2 dark:border-gray-700">
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="split in itemSplits"
                                        :key="split.id"
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-200 flex items-center gap-1.5"
                                        :class="activeSplitId === split.id
                                            ? 'bg-purple-600 border-purple-600 text-white shadow-sm'
                                            : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                        @click="activeSplitId = split.id"
                                    >
                                        Split {{ split.id }}
                                        <span
                                            v-if="itemSplits.length > 1 && split.id !== 1"
                                            class="text-gray-400 hover:text-red-300 transition-colors text-sm leading-none"
                                            @click.stop="removeItemSplit(split.id)"
                                        >
                                            &times;
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="addItemSplit"
                                    >
                                        + New Split
                                    </button>
                                </div>
                            </div>

                            <!-- Dual Columns: Items list & assigned list -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Left: Available Items -->
                                <div class="border dark:border-gray-700 rounded-lg p-3 bg-gray-50/50 dark:bg-gray-900/30 flex flex-col h-[40vh]">
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                        Available Items (Click to assign)
                                    </div>
                                    <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                                        <div v-if="loadingOrder" class="text-center py-8 text-gray-500">Loading order items...</div>
                                        <div v-else-if="availableItemsList.length === 0" class="text-center py-8 text-gray-500">
                                            All items assigned
                                        </div>
                                        <div
                                            v-else
                                            v-for="item in availableItemsList"
                                            :key="item.order_item_id"
                                            class="p-2 border dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-between gap-3 shadow-sm hover:shadow"
                                        >
                                            <div class="min-w-0">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ item.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Remaining: {{ item.remaining }} / {{ item.qty }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 mr-1">
                                                    {{ currencySymbol }}{{ formatMoney(item.price) }}
                                                </span>
                                                <button
                                                    type="button"
                                                    class="py-1 px-2 border dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200"
                                                    @click="addItemToSplit(item.order_item_id, 1)"
                                                >
                                                    +1
                                                </button>
                                                <button
                                                    type="button"
                                                    class="py-1 px-2 border dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-600 font-semibold text-gray-700 dark:text-gray-200"
                                                    @click="addItemToSplit(item.order_item_id, item.remaining)"
                                                >
                                                    All
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Assigned Items in active split -->
                                <div class="border dark:border-gray-700 rounded-lg p-3 bg-gray-50/50 dark:bg-gray-900/30 flex flex-col h-[40vh]">
                                    <div class="flex items-center justify-between border-b pb-2 mb-2 dark:border-gray-700">
                                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Items in Split {{ activeSplitId }}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Payment:</span>
                                            <select
                                                v-model="activeSplitPaymentMethod"
                                                class="py-0.5 px-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs"
                                            >
                                                <option v-for="method in splitMethods" :key="method.id" :value="method.id">
                                                    {{ method.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                                        <div v-if="activeSplitItems.length === 0" class="text-center py-8 text-gray-500">
                                            No items in this split
                                        </div>
                                        <div
                                            v-else
                                            v-for="(item, index) in activeSplitItems"
                                            :key="item.order_item_id"
                                            class="p-2 border dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-between gap-3 shadow-sm hover:shadow"
                                        >
                                            <div class="min-w-0">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ item.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ currencySymbol }}{{ formatMoney(item.price) }} each
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <button
                                                    type="button"
                                                    class="w-6 h-6 flex items-center justify-center border dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200"
                                                    @click="decrementItemInSplit(activeSplitId, index)"
                                                >
                                                    -
                                                </button>
                                                <span class="text-sm font-semibold w-4 text-center dark:text-white">{{ item.quantity }}</span>
                                                <button
                                                    type="button"
                                                    class="w-6 h-6 flex items-center justify-center border dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200"
                                                    @click="incrementItemInSplit(activeSplitId, index)"
                                                >
                                                    +
                                                </button>
                                                <button
                                                    type="button"
                                                    class="text-xs text-red-500 hover:underline ml-1"
                                                    @click="removeItemFromSplit(activeSplitId, index)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t pt-2 mt-2 dark:border-gray-700 flex justify-between items-center text-sm font-bold text-purple-700 dark:text-purple-300">
                                        <span>Split Total</span>
                                        <span>{{ currencySymbol }}{{ formatMoney(activeSplitTotal) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Split summary -->
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm space-y-1">
                                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                                    <span>All Splits Total</span>
                                    <span class="font-semibold">{{ currencySymbol }}{{ formatMoney(itemsSplitTotal) }}</span>
                                </div>
                                <div class="flex justify-between text-orange-600 dark:text-orange-400">
                                    <span>Remaining Unassigned</span>
                                    <span class="font-bold">{{ currencySymbol }}{{ formatMoney(itemsRemainingAmount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <button
                    type="button"
                    class="flex-1 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium"
                    :disabled="submitting"
                    @click="emitClose"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="flex-1 px-4 py-3 rounded-lg bg-skin-base text-white hover:opacity-90 disabled:opacity-50 font-medium transition-all"
                    :disabled="!canSubmit"
                    @click="submit"
                >
                    {{ submitting ? 'Processing…' : isSplit ? 'Complete Split Payment' : 'Complete Payment' }}
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

const orderDetails = ref(null);
const loadingOrder = ref(false);

// Split parameters
const isSplit = ref(false);
const splitType = ref(null); // 'equal', 'custom', 'items'

// For Equal Split
const numberOfSplits = ref(2);
const equalSplits = ref([
    { payment_method: "cash" },
    { payment_method: "cash" }
]);

// For Custom Split
const customSplits = ref([
    { amount: "", payment_method: "cash" },
    { amount: "", payment_method: "cash" }
]);

// For Items Split
const itemSplits = ref([
    { id: 1, payment_method: "cash", items: [] }
]);
const activeSplitId = ref(1);

const methods = [
    { id: "cash", label: "Cash" },
    { id: "card", label: "Card" },
    { id: "upi", label: "UPI" },
    { id: "bank_transfer", label: "Bank Transfer" },
    { id: "due", label: "Due" },
];

const splitMethods = [
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

// Eager computation helper for extra charges of an order
const calculateTotalExtraCharges = (orderVal) => {
    if (!orderVal) return 0;
    const totalDiscount = parseFloat(orderVal.discount_amount || 0);
    const subTotal = parseFloat(orderVal.sub_total || 0);
    const discountedSubTotal = Math.max(0, subTotal - totalDiscount);

    let totalExtra = 0;
    if (orderVal.charges && Array.isArray(orderVal.charges)) {
        orderVal.charges.forEach(c => {
            if (c.charge) {
                if (c.charge.charge_type === 'percent') {
                    totalExtra += (parseFloat(c.charge.charge_value) / 100) * discountedSubTotal;
                } else {
                    totalExtra += parseFloat(c.charge.charge_value);
                }
            }
        });
    }
    return totalExtra;
};

// Available unpaid order items calculation
const availableItemsList = computed(() => {
    if (!orderDetails.value || !orderDetails.value.lines) return [];

    const paidItemQuantities = orderDetails.value.paid_item_quantities || {};
    const orderVal = orderDetails.value;
    const totalDiscount = parseFloat(orderVal.discount_amount || 0);
    const totalTip = parseFloat(orderVal.tip_amount || 0);
    const totalBaseAmount = orderVal.lines.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
    const totalQuantity = orderVal.lines.reduce((sum, item) => sum + parseInt(item.qty || 0), 0);

    const taxMode = window.restaurant?.tax_mode || 'item';
    const totalExtraCharges = calculateTotalExtraCharges(orderVal);

    return orderVal.lines.map(line => {
        const orderItemId = line.order_item_id;
        const qty = parseInt(line.qty || 1);
        const amount = parseFloat(line.amount || 0);

        const unitBasePrice = amount / qty;
        const itemDiscount = totalBaseAmount > 0 ? (amount / totalBaseAmount) * totalDiscount : 0;
        const itemTip = totalBaseAmount > 0 ? (amount / totalBaseAmount) * totalTip : 0;
        const unitDiscount = itemDiscount / qty;
        const unitTip = itemTip / qty;
        const unitBasePriceAfterDiscount = unitBasePrice - unitDiscount;
        const itemExtraCharges = totalExtraCharges / totalQuantity;

        let itemTaxAmount = 0;
        if (taxMode === 'item') {
            itemTaxAmount = parseFloat(line.tax_amount || 0) / qty;
        } else {
            if (parseFloat(orderVal.total || 0) > 0 && orderVal.taxes) {
                orderVal.taxes.forEach(t => {
                    const percent = t.tax ? parseFloat(t.tax.tax_percent) : 0;
                    itemTaxAmount += (percent / 100) * unitBasePriceAfterDiscount;
                });
            }
        }

        // proportional check to match total exactly
        const calculatedUnitPrice = unitBasePriceAfterDiscount + itemTaxAmount + itemExtraCharges + unitTip;
        const fallbackUnitPrice = totalBaseAmount > 0 ? (amount / totalBaseAmount) * parseFloat(orderVal.total || 0) / qty : calculatedUnitPrice;
        const unitTotalPrice = isNaN(fallbackUnitPrice) || fallbackUnitPrice <= 0 ? calculatedUnitPrice : fallbackUnitPrice;

        const paidQty = paidItemQuantities[orderItemId] || 0;

        let assignedQty = 0;
        itemSplits.value.forEach(s => {
            const found = s.items.find(si => si.order_item_id === orderItemId);
            if (found) {
                assignedQty += found.quantity;
            }
        });

        const remainingQty = Math.max(0, qty - paidQty - assignedQty);

        return {
            order_item_id: orderItemId,
            name: line.item_name,
            qty: qty,
            paid_qty: paidQty,
            remaining: remainingQty,
            price: unitTotalPrice,
        };
    }).filter(item => item.remaining > 0);
});

// Items Split Computed Helpers
const activeSplit = computed(() => {
    return itemSplits.value.find(s => s.id === activeSplitId.value);
});

const activeSplitItems = computed(() => {
    return activeSplit.value ? activeSplit.value.items : [];
});

const activeSplitPaymentMethod = computed({
    get: () => {
        return activeSplit.value ? activeSplit.value.payment_method : "cash";
    },
    set: (val) => {
        if (activeSplit.value) {
            activeSplit.value.payment_method = val;
        }
    }
});

const activeSplitTotal = computed(() => {
    return activeSplitItems.value.reduce((sum, item) => sum + (Number(item.price || 0) * item.quantity), 0);
});

const itemsSplitTotal = computed(() => {
    return itemSplits.value.reduce((sum, s) => {
        const splitSum = s.items.reduce((itemSum, item) => itemSum + (Number(item.price || 0) * item.quantity), 0);
        return sum + splitSum;
    }, 0);
});

const itemsRemainingAmount = computed(() => {
    const outstanding = Number(props.dueAmount || 0);
    return Math.max(0, outstanding - itemsSplitTotal.value);
});

// Equal Splits Computed Helpers
const equalSplitAmount = computed(() => {
    const outstanding = Number(props.dueAmount || 0);
    if (numberOfSplits.value <= 0) return 0;
    return outstanding / numberOfSplits.value;
});

// Custom Splits Computed Helpers
const customSplitTotal = computed(() => {
    return customSplits.value.reduce((sum, s) => sum + Number(s.amount || 0), 0);
});

const customRemainingDue = computed(() => {
    const outstanding = Number(props.dueAmount || 0);
    return Math.max(0, outstanding - customSplitTotal.value);
});

const customReturnAmount = computed(() => {
    const outstanding = Number(props.dueAmount || 0);
    return Math.max(0, customSplitTotal.value - outstanding);
});

// Split management functions
const selectSplitType = (type) => {
    splitType.value = type;
    if (type === 'equal') {
        numberOfSplits.value = 2;
        updateEqualSplits();
    } else if (type === 'custom') {
        customSplits.value = [
            { amount: "", payment_method: "cash" },
            { amount: "", payment_method: "cash" }
        ];
    } else if (type === 'items') {
        itemSplits.value = [
            { id: 1, payment_method: "cash", items: [] }
        ];
        activeSplitId.value = 1;
    }
};

const updateEqualSplits = () => {
    while (equalSplits.value.length < numberOfSplits.value) {
        equalSplits.value.push({ payment_method: "cash" });
    }
    while (equalSplits.value.length > numberOfSplits.value) {
        equalSplits.value.pop();
    }
};

const incrementEqualSplits = () => {
    numberOfSplits.value++;
    updateEqualSplits();
};

const decrementEqualSplits = () => {
    if (numberOfSplits.value > 2) {
        numberOfSplits.value--;
        updateEqualSplits();
    }
};

const addCustomSplit = () => {
    customSplits.value.push({ amount: "", payment_method: "cash" });
};

const removeCustomSplit = (index) => {
    if (customSplits.value.length > 2) {
        customSplits.value.splice(index, 1);
    }
};

const addItemSplit = () => {
    const nextId = itemSplits.value.length > 0
        ? Math.max(...itemSplits.value.map(s => s.id)) + 1
        : 1;
    itemSplits.value.push({ id: nextId, payment_method: "cash", items: [] });
    activeSplitId.value = nextId;
};

const removeItemSplit = (id) => {
    if (itemSplits.value.length > 1) {
        const index = itemSplits.value.findIndex(s => s.id === id);
        if (index !== -1) {
            itemSplits.value.splice(index, 1);
            if (activeSplitId.value === id) {
                activeSplitId.value = itemSplits.value[0].id;
            }
        }
    }
};

const addItemToSplit = (orderItemId, quantityToAdd) => {
    const item = availableItemsList.value.find(i => i.order_item_id === orderItemId);
    if (!item || item.remaining <= 0) return;

    const qty = Math.min(item.remaining, quantityToAdd);
    if (qty <= 0) return;

    const split = itemSplits.value.find(s => s.id === activeSplitId.value);
    if (!split) return;

    const existing = split.items.find(si => si.order_item_id === orderItemId);
    if (existing) {
        existing.quantity += qty;
    } else {
        split.items.push({
            order_item_id: orderItemId,
            name: item.name,
            quantity: qty,
            price: item.price
        });
    }
};

const decrementItemInSplit = (splitId, itemIndex) => {
    const split = itemSplits.value.find(s => s.id === splitId);
    if (!split) return;

    const item = split.items[itemIndex];
    if (item.quantity > 1) {
        item.quantity--;
    } else {
        split.items.splice(itemIndex, 1);
    }
};

const incrementItemInSplit = (splitId, itemIndex) => {
    const split = itemSplits.value.find(s => s.id === splitId);
    if (!split) return;

    const item = split.items[itemIndex];
    const origItem = availableItemsList.value.find(i => i.order_item_id === item.order_item_id);
    if (origItem && origItem.remaining > 0) {
        item.quantity++;
    }
};

const removeItemFromSplit = (splitId, itemIndex) => {
    const split = itemSplits.value.find(s => s.id === splitId);
    if (!split) return;

    split.items.splice(itemIndex, 1);
};

const fetchOrderDetails = async () => {
    loadingOrder.value = true;
    try {
        const response = await axios.get(`/api/pos/orders/${props.orderId}`);
        orderDetails.value = response.data?.data?.order || null;
    } catch (error) {
        console.error("Error fetching order details for payment:", error);
    } finally {
        loadingOrder.value = false;
    }
};

const canSubmit = computed(() => {
    if (props.saving || props.submitting || !props.orderId) {
        return false;
    }
    if (!isSplit.value) {
        if (paymentMethod.value === "due") {
            return true;
        }
        return payableAmount.value > 0;
    }

    if (splitType.value === "equal") {
        return numberOfSplits.value >= 2;
    }
    if (splitType.value === "custom") {
        const diff = Math.abs(customSplitTotal.value - Number(props.dueAmount || 0));
        return customSplits.value.length >= 2 && diff < 0.05;
    }
    if (splitType.value === "items") {
        const hasAssignedItems = itemSplits.value.some(s => s.items.length > 0);
        return hasAssignedItems;
    }

    return false;
});

const formatMoney = (value) => {
    const n = Number(value || 0);
    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const resetLocal = () => {
    paymentMethod.value = "cash";
    amountTouched.value = false;
    amountInput.value = Number(props.dueAmount || 0) > 0 ? String(Number(props.dueAmount || 0)) : "";

    isSplit.value = false;
    splitType.value = null;
    numberOfSplits.value = 2;
    equalSplits.value = [
        { payment_method: "cash" },
        { payment_method: "cash" }
    ];
    customSplits.value = [
        { amount: "", payment_method: "cash" },
        { amount: "", payment_method: "cash" }
    ];
    itemSplits.value = [
        { id: 1, payment_method: "cash", items: [] }
    ];
    activeSplitId.value = 1;
    orderDetails.value = null;
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

    if (!isSplit.value) {
        emit("submit", {
            order_id: Number(props.orderId),
            payment_method: paymentMethod.value,
            amount: paymentMethod.value === "due" ? 0 : payableAmount.value,
        });
    } else {
        if (splitType.value === "equal") {
            const splitsPayload = equalSplits.value.map(s => ({
                payment_method: s.payment_method,
                amount: Number(equalSplitAmount.value.toFixed(2))
            }));
            emit("submit", {
                order_id: Number(props.orderId),
                split_type: "equal",
                splits: splitsPayload
            });
        } else if (splitType.value === "custom") {
            const splitsPayload = customSplits.value.map(s => ({
                payment_method: s.payment_method,
                amount: Number(parseFloat(s.amount || 0).toFixed(2))
            }));
            emit("submit", {
                order_id: Number(props.orderId),
                split_type: "custom",
                splits: splitsPayload
            });
        } else if (splitType.value === "items") {
            const nonArr = itemSplits.value.filter(s => s.items.length > 0);
            const splitsPayload = nonArr.map(s => ({
                payment_method: s.payment_method,
                items: s.items.map(si => ({
                    order_item_id: si.order_item_id,
                    quantity: si.quantity,
                    price: si.price
                }))
            }));
            emit("submit", {
                order_id: Number(props.orderId),
                split_type: "items",
                splits: splitsPayload
            });
        }
    }
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            resetLocal();
            if (props.orderId) {
                fetchOrderDetails();
            }
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
