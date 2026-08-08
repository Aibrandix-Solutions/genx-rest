<template>
  <div v-cloak>
    <!-- Drawer Overlay Backdrop (z-40 so modals at z-50 and Swal at z-1060 stack above) -->
    <div
      v-if="visible"
      class="fixed inset-0 bg-black/50 backdrop-blur-[1px] transition-opacity duration-300"
      style="z-index: 40;"
      @click="closeDrawer"
    ></div>

    <!-- Right Side Drawer (z-45) -->
    <div
      v-if="visible"
      class="fixed inset-y-0 right-0 flex"
      style="z-index: 45;"
    >
      <div class="relative w-screen max-w-xl bg-white dark:bg-gray-800 shadow-2xl flex flex-col h-full border-l border-gray-200 dark:border-gray-700">

        <!-- ─── No data at all (shouldn't be seen with preview data) -->
        <div v-if="!order && !previewData && !loading" class="flex-1 flex flex-col items-center justify-center p-8 text-gray-500 dark:text-gray-400">
          <span class="text-sm font-medium">No order found.</span>
        </div>

        <!-- ─── Pure spinner: only if no preview yet (first open before event has data) -->
        <div v-else-if="loading && !order && !previewData" class="flex-1 flex flex-col items-center justify-center p-8 text-gray-500 dark:text-gray-400">
          <svg class="animate-spin h-7 w-7 text-indigo-500 mb-3" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-sm font-medium">Loading...</span>
        </div>

        <!-- ─── Main Content (shows instantly from previewData, enriched once order loads) -->
        <template v-else-if="order || previewData">
          <!-- Thin progress bar on refresh -->
          <div v-if="loading" class="absolute top-0 left-0 right-0 h-0.5 bg-indigo-200 dark:bg-indigo-900 overflow-hidden" style="z-index: 46;">
            <div class="h-full bg-indigo-500 animate-pulse" style="width: 60%;"></div>
          </div>

          <div class="flex-1 overflow-y-auto p-5 space-y-5">

            <!-- ── Header (uses previewData immediately, order when loaded) ──── -->
            <div class="flex justify-between items-start gap-3">
              <!-- Left: order number, table/customer, waiter, rider -->
              <div class="flex-1 min-w-0 space-y-2">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight truncate">
                  {{ (order || previewData).formatted_order_number || ('Order #' + (order || previewData).order_number) }}
                </h2>

                <!-- Table + Customer row -->
                <div class="flex flex-wrap items-center gap-2">
                  <!-- Assign Table Button (opens modal) -->
                  <button
                    v-if="order.order_type === 'dine_in'"
                    @click="showTableModal = true"
                    :disabled="!order.permissions.can_update_order"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm disabled:opacity-50"
                  >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                    <span>{{ order.table_code || 'Assign Table' }}</span>
                    <svg v-if="order.table_code" class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>

                  <!-- Customer: name or Add link + edit/remove -->
                  <div class="flex items-center gap-1">
                    <span v-if="order.customer?.name" class="text-sm font-semibold text-gray-800 dark:text-white truncate max-w-[120px]" :title="order.customer.name">
                      {{ order.customer.name }}
                    </span>
                    <button
                      v-else
                      @click="triggerAddCustomerModal"
                      class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                      Add Customer
                    </button>

                    <!-- Edit customer -->
                    <button
                      @click="triggerAddCustomerModal"
                      class="p-1 rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                      title="Edit Customer"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>

                    <!-- Remove customer -->
                    <button
                      v-if="order.customer_id"
                      @click="removeCustomer"
                      class="p-1 rounded border border-gray-200 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-colors"
                      title="Remove Customer"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>

                <!-- Timestamp -->
                <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ formatDateTime(order.date_time || order.created_at) }}</div>

                <!-- Waiter selector -->
                <div class="flex flex-wrap gap-2 pt-0.5">
                  <div class="relative">
                    <select
                      v-model="selectedWaiterId"
                      @change="updateWaiter"
                      :disabled="!order.permissions.can_update_order"
                      class="pl-3 pr-7 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 focus:outline-none cursor-pointer appearance-none shadow-sm disabled:opacity-50 max-w-[160px]"
                    >
                      <option :value="null">Select Waiter</option>
                      <option v-for="w in waiters" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                    <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-gray-400">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                  </div>

                  <!-- Rider selector for delivery -->
                  <div v-if="order.order_type === 'delivery'" class="relative">
                    <select
                      v-model="selectedDeliveryExecutiveId"
                      @change="updateDeliveryExecutive"
                      :disabled="!order.permissions.can_update_order"
                      class="pl-3 pr-7 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 focus:outline-none cursor-pointer appearance-none shadow-sm disabled:opacity-50 max-w-[160px]"
                    >
                      <option :value="null">Select Rider</option>
                      <option v-for="d in deliveryExecutives" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-gray-400">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right: Order type, status badges, date -->
              <div class="flex flex-col items-end space-y-2 flex-shrink-0">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 capitalize">
                    {{ (order.order_type || '').replace('_', ' ') }}
                  </span>
                </div>

                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide"
                  :class="{
                    'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800': order.status === 'kot',
                    'bg-yellow-50 text-yellow-700 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800': order.status === 'billed',
                    'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800': order.status === 'paid',
                    'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800': order.status === 'canceled',
                    'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800': order.status === 'payment_due',
                    'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600': !['kot','billed','paid','canceled','payment_due'].includes(order.status),
                  }"
                >{{ order.status }}</span>

                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-900/30 dark:text-violet-300 dark:border-violet-800">
                  {{ order.placed_via || 'POS' }}
                </span>
              </div>
            </div>

            <!-- ── Order Status Stepper ──────────────────────────── -->
            <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
              <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Order Status</h3>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 uppercase">
                  {{ orderStatusLabel(order.order_status) }}
                </span>
              </div>

              <div class="flex justify-between items-start relative">
                <div v-for="(status, i) in activeStatusList" :key="status" class="flex flex-col items-center flex-1 relative">
                  <!-- Connector line -->
                  <div v-if="i < activeStatusList.length - 1"
                    class="absolute top-3.5 left-1/2 w-full h-0.5 z-0"
                    :class="i < currentStatusIndex ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-600'"
                  ></div>
                  <!-- Dot -->
                  <div class="w-7 h-7 rounded-full z-10 flex items-center justify-center text-white transition-colors"
                    :class="i <= currentStatusIndex ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
                  >
                    <svg v-if="i <= currentStatusIndex" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                  <span class="text-[9px] text-center mt-1 font-medium text-gray-500 dark:text-gray-400 leading-tight px-0.5">{{ orderStatusLabel(status) }}</span>
                </div>
              </div>

              <div v-if="order.permissions.can_update_order && currentStatusIndex < activeStatusList.length - 1" class="flex justify-end pt-1">
                <button @click="moveToNextStatus"
                  class="inline-flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-bold rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm"
                >
                  Move to {{ orderStatusLabel(activeStatusList[currentStatusIndex + 1]) }}
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
              </div>
            </div>

            <!-- ── Items Table ───────────────────────────────────── -->
            <div class="border-t border-gray-100 dark:border-gray-700 pt-4">

              <!-- Header row -->
              <div class="flex text-[10px] font-bold uppercase tracking-wider px-2 py-2 rounded mb-1"
                :style="isDark ? 'background:#374151;color:#9ca3af' : 'background:#f3f4f6;color:#6b7280'"
              >
                <div class="flex-1 min-w-0 pr-2">Item Name</div>
                <div class="w-16 text-center flex-shrink-0">Qty</div>
                <div class="w-20 text-right flex-shrink-0">Price</div>
                <div class="w-20 text-right flex-shrink-0">Amount</div>
                <div class="w-8 flex-shrink-0"></div>
              </div>

              <!-- Item rows -->
              <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <div v-for="line in order.lines" :key="line.order_item_id" class="flex items-start px-2 py-2.5 gap-1 text-xs">

                  <!-- Name column (flex-1, takes remaining space) -->
                  <div class="flex-1 min-w-0 pr-2">
                    <div class="font-semibold text-gray-800 dark:text-white leading-snug truncate" :title="line.item_name">{{ line.item_name }}</div>
                    <div v-if="line.variation_name" class="text-[9px] text-gray-400 mt-0.5">Var: {{ line.variation_name }}</div>
                    <div v-if="Number(line.item_discount_amount || 0) > 0" class="text-[10px] text-green-600 dark:text-green-400 mt-0.5">
                      Discount: -{{ formatCurrency(line.item_discount_amount) }}
                      <span v-if="line.discount_type === 'percent'">({{ Number(line.discount_value || 0) }}%)</span>
                    </div>
                    <div v-if="line.modifier_option_details?.length" class="mt-1 space-y-1">
                      <div
                        v-for="mod in line.modifier_option_details"
                        :key="mod.id"
                        class="flex justify-between items-center px-1.5 py-0.5 text-[10px] bg-gray-100 dark:bg-gray-900 rounded border-l-2 border-blue-500 text-gray-800 dark:text-gray-200"
                      >
                        <span class="truncate pr-1">
                          +{{ mod.name }}<span v-if="(line.modifier_option_quantities?.[mod.id] || 1) > 1"> ×{{ line.modifier_option_quantities[mod.id] }}</span>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap ml-1">
                          {{ formatCurrency((mod.price || 0) * (line.modifier_option_quantities?.[mod.id] || 1)) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Qty column (fixed 64px) -->
                  <div class="w-16 flex-shrink-0 flex justify-center items-start pt-0.5">
                    <div class="inline-flex items-center border border-gray-300 dark:border-gray-600 rounded overflow-hidden">
                      <button
                        @click="reduceKotItem(line)"
                        :disabled="!order.permissions.can_delete_kot_item || order.status === 'canceled'"
                        class="w-5 h-6 flex items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50 hover:bg-gray-100 dark:bg-gray-600/50 dark:hover:bg-gray-600 font-bold text-sm leading-none disabled:opacity-30 select-none"
                      >−</button>
                      <span class="w-6 h-6 flex items-center justify-center text-[11px] font-bold text-gray-800 dark:text-gray-200 select-none">{{ line.qty }}</span>
                    </div>
                  </div>

                  <!-- Price column (fixed 80px) -->
                  <div class="w-20 flex-shrink-0 text-right pt-0.5">
                    <span class="text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap">{{ formatCurrency(line.unit_price) }}</span>
                  </div>

                  <!-- Amount column (fixed 80px) -->
                  <div class="w-20 flex-shrink-0 text-right pt-0.5">
                    <span class="font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ formatCurrency(line.amount) }}</span>
                  </div>

                  <!-- Action column (fixed 32px) -->
                  <div class="w-8 flex-shrink-0 flex justify-end items-start pt-0.5">
                    <button
                      v-if="order.permissions.can_delete_kot_item && order.status !== 'canceled'"
                      @click="deleteKotItem(line)"
                      class="w-6 h-6 flex items-center justify-center rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-colors shadow-sm"
                      title="Remove item"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>

                <!-- Empty state -->
                <div v-if="!order.lines?.length" class="py-6 text-center text-xs text-gray-400">No items found.</div>
              </div>
            </div>

            <!-- ── Summary Box ───────────────────────────────────── -->
            <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-700/60 p-4 space-y-2 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>Item(s)</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ order.lines?.length || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span>Sub Total</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ formatCurrency(order.sub_total) }}</span>
              </div>

              <!-- Delivery Fee (inline edit, only for delivery orders) -->
              <div v-if="order.order_type === 'delivery'" class="flex justify-between items-center">
                <span>Delivery Fee</span>
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] text-gray-400">Edit:</span>
                  <input
                    type="number" step="0.01" min="0"
                    v-model.number="deliveryFeeInput"
                    @blur="updateDeliveryFee"
                    @keyup.enter="$event.target.blur()"
                    :disabled="!order.permissions.can_update_order"
                    class="w-16 px-1.5 py-0.5 text-right text-xs border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded text-gray-800 dark:text-white font-medium disabled:opacity-50"
                  />
                </div>
              </div>

              <!-- Discount -->
              <div v-if="order.discount_amount > 0" class="flex justify-between items-center text-red-500 dark:text-red-400">
                <span class="flex items-center gap-1">
                  Discount ({{ order.discount_type === 'percent' ? order.discount_value + '%' : 'Fixed' }})
                  <button v-if="order.permissions.can_update_order" @click="removeDiscount" class="font-bold text-red-400 hover:text-red-600 leading-none">×</button>
                </span>
                <span>-{{ formatCurrency(order.discount_amount) }}</span>
              </div>

              <!-- Custom extras -->
              <div v-for="extra in order.custom_extras" :key="extra.note" class="flex justify-between text-gray-500 italic">
                <span>+ {{ extra.note }}</span>
                <span>{{ formatCurrency(extra.amount) }}</span>
              </div>

              <!-- Total -->
              <div class="flex justify-between font-bold text-sm text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-2">
                <span>Total</span>
                <span>{{ formatCurrency(order.total) }}</span>
              </div>
              <div v-if="order.amount_paid > 0" class="flex justify-between text-green-600 dark:text-green-400 font-semibold text-xs">
                <span>Amount Paid</span>
                <span>{{ formatCurrency(order.amount_paid) }}</span>
              </div>
              <div v-if="order.due_amount > 0" class="flex justify-between text-red-600 dark:text-red-400 font-semibold text-xs">
                <span>Due Amount</span>
                <span>{{ formatCurrency(order.due_amount) }}</span>
              </div>
              <div class="flex justify-between text-gray-400 text-xs">
                <span>Balance Returned</span>
                <span>{{ formatCurrency(0) }}</span>
              </div>
            </div>

            <!-- ── Payments Recorded (Legacy Parity) ─────────────── -->
            <div v-if="order.payments && order.payments.length" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden space-y-0 text-xs">
              <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-[11px] flex justify-between items-center">
                <span>Payments Recorded</span>
                <span>{{ order.payments.length }}</span>
              </div>
              <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th class="p-2 text-left font-medium text-gray-500 dark:text-gray-400">Amount</th>
                    <th class="p-2 text-center font-medium text-gray-500 dark:text-gray-400">Method</th>
                    <th class="p-2 text-right font-medium text-gray-500 dark:text-gray-400">Date Time</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                  <tr v-for="pay in order.payments" :key="pay.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-2 font-medium text-gray-900 dark:text-gray-200">
                      {{ formatCurrency(pay.amount) }}
                    </td>
                    <td class="p-2 text-center">
                      <select
                        v-if="order.permissions?.can_update_order && order.status !== 'pending_verification'"
                        :value="pay.payment_method"
                        @change="changePaymentMethod(pay.id, $event.target.value)"
                        class="text-xs py-0.5 px-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                      >
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="due">Due</option>
                        <option value="bank_transfer">Bank Transfer</option>
                      </select>
                      <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold capitalize"
                            :class="pay.payment_method === 'due' ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'">
                        {{ pay.payment_method.replace('_', ' ') }}
                      </span>
                    </td>
                    <td class="p-2 text-right text-gray-500 dark:text-gray-400 text-[11px]">
                      {{ pay.formatted_date || formatDateTime(pay.created_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- ── Action Buttons ────────────────────────────────── -->
            <div class="space-y-2">
              <!-- Pay Button -->
              <button
                v-if="!['paid', 'canceled'].includes(order.status)"
                @click="openPaymentModal"
                class="w-full py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 transition-colors shadow"
                style="background-color: #047857; color: #fff;"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Add Payment
              </button>

              <!-- New KOT -->
              <button
                @click="redirectToPos"
                class="w-full py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 transition-colors shadow"
                style="background-color: #1e293b; color: #fff;"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New KOT
              </button>

              <!-- Discount toggle -->
              <button
                v-if="order.permissions.can_update_order && !showAddDiscount"
                @click="showAddDiscount = true"
                class="w-full py-2.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors border"
                :style="isDark ? 'background:#374151;color:#e5e7eb;border-color:#4b5563' : 'background:#fff;color:#374151;border-color:#d1d5db'"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Add Discount
              </button>

              <div v-if="showAddDiscount" class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                <select v-model="discountTypeInput" class="text-xs bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
                  <option value="percent">%</option>
                  <option value="fixed">Fixed</option>
                </select>
                <input type="number" step="0.01" v-model="discountValueInput" placeholder="Value"
                  class="flex-1 min-w-0 px-2 py-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded text-xs text-right text-gray-800 dark:text-white"
                />
                <button @click="applyDiscount" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold">Apply</button>
                <button @click="showAddDiscount = false" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded text-xs font-bold">×</button>
              </div>
            </div>
          </div>

          <!-- ─── Footer Buttons ─────────────────────────────────── -->
          <div class="p-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-700 grid grid-cols-4 gap-2 flex-shrink-0">
            <!-- Print Receipt -->
            <button @click="printReceipt()"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors border"
              :style="isDark ? 'background:#374151;color:#fff;border-color:#4b5563' : 'background:#fff;color:#374151;border-color:#d1d5db'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              Print
            </button>

            <!-- Cancel Order -->
            <button
              v-if="order.status !== 'canceled'"
              @click="triggerCancelOrder"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors"
              style="background:#dc2626;color:#fff;"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Cancel
            </button>

            <!-- Delete Order -->
            <button
              v-if="order.permissions.can_delete_order"
              @click="triggerDeleteOrder"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors"
              style="background:#ef4444;color:#fff;"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Delete
            </button>

            <!-- Close -->
            <button @click="closeDrawer"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors border"
              :style="isDark ? 'background:#4b5563;color:#fff;border-color:#6b7280' : 'background:#e5e7eb;color:#374151;border-color:#d1d5db'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Close
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- ─── Table Assignment Modal (reuses existing full modal component) ─── -->
    <TableAssignmentModal
      v-if="showTableModal"
      :show="showTableModal"
      @close="showTableModal = false"
      @select="handleTableSelected"
    />

    <!-- ─── Payment Modal ─── -->
    <PosPaymentModal
      v-if="showPaymentModal"
      :show="showPaymentModal"
      :order-id="orderId"
      :due-amount="order?.due_amount !== undefined ? order.due_amount : (order?.total || 0)"
      :currency-symbol="order?.currency_symbol || 'Rs'"
      :saving="paymentSaving"
      :preloaded-order="order"
      :show-room-charge="order?.show_room_charge || false"
      @close="showPaymentModal = false"
      @submit="handlePaymentSubmit"
    />

    <!-- ─── Add Customer Modal ─── -->
    <AddCustomerModal
      :show="showAddCustomerModal"
      :customer="order?.customer"
      @close="showAddCustomerModal = false"
      @save="handleDrawerSaveCustomer"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import PosPaymentModal from './PosPaymentModal.vue';
import TableAssignmentModal from './TableAssignmentModal.vue';
import AddCustomerModal from './AddCustomerModal.vue';

// ─── State ───────────────────────────────────────────────────────────────────
const visible = ref(false);
const loading = ref(false);
const orderId = ref(null);
const order = ref(null);
// previewData: populated from the event payload so the header renders before
// the full /api/pos/orders/{id} response arrives
const previewData = ref(null);
const previewOrderNumber = ref(null); // legacy compat — previewData supersedes this

// Theme
const isDark = ref(document.documentElement.classList.contains('dark'));

// Dropdown data
const waiters = ref([]);
const deliveryExecutives = ref([]);
const cancelReasons = ref([]);

// Inputs bound to order data
const selectedWaiterId = ref(null);
const selectedDeliveryExecutiveId = ref(null);
const deliveryFeeInput = ref(0);

// Discount
const showAddDiscount = ref(false);
const discountTypeInput = ref('percent');
const discountValueInput = ref(null);

// Modals
const showTableModal = ref(false);
const showPaymentModal = ref(false);
const paymentSaving = ref(false);
const showAddCustomerModal = ref(false);
const pendingDrawerAction = ref(null);

// ─── Stepper ─────────────────────────────────────────────────────────────────
const activeStatusList = computed(() => {
  const type = order.value?.order_type || 'dine_in';
  if (type === 'delivery') return ['placed', 'confirmed', 'preparing', 'food_ready', 'out_for_delivery', 'delivered'];
  if (type === 'pickup')   return ['placed', 'confirmed', 'preparing', 'food_ready', 'ready_for_pickup', 'delivered'];
  return ['placed', 'confirmed', 'preparing', 'food_ready', 'served'];
});

const currentStatusIndex = computed(() => {
  const idx = activeStatusList.value.indexOf(order.value?.order_status);
  return idx >= 0 ? idx : 0;
});

const orderStatusLabel = (s) => ({
  placed: 'Placed', confirmed: 'Confirmed', preparing: 'Preparing', food_ready: 'Ready',
  out_for_delivery: 'Out For Delivery', ready_for_pickup: 'Pickup Ready',
  delivered: 'Delivered', served: 'Served', cancelled: 'Cancelled',
}[s] || s);

// ─── Helpers ─────────────────────────────────────────────────────────────────
const formatDateTime = (dt) => {
  if (!dt) return '';
  return new Date(dt).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
};

const formatCurrency = (val) => {
  const sym = order.value?.currency_symbol || 'Rs';
  return `${sym}${Number(val || 0).toFixed(2)}`;
};

const dispatchLivewireEvent = (name, payload = null) => {
  if (window.Livewire) {
    payload ? window.Livewire.dispatch(name, payload) : window.Livewire.dispatch(name);
  }
};

const showToast = (msg, icon = 'success') => {
  if (window.Swal) {
    Swal.fire({ icon, title: msg, toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
  }
};

// ─── Load Order ───────────────────────────────────────────────────────────────
const fetchOrderDetails = async (id) => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/pos/orders/${id}`);
    const data = res.data?.data || {};
    order.value = data.order || null;
    waiters.value = data.waiters || [];
    deliveryExecutives.value = data.delivery_executives || [];
    cancelReasons.value = data.cancel_reasons || [];

    if (order.value) {
      selectedWaiterId.value = order.value.waiter_id;
      selectedDeliveryExecutiveId.value = order.value.delivery_executive_id;
      deliveryFeeInput.value = order.value.delivery_fee;
      discountTypeInput.value = order.value.discount_type || 'percent';
      discountValueInput.value = order.value.discount_value || null;
    }
  } catch (err) {
    console.error('Failed to load order:', err);
    if (window.Swal) {
      Swal.fire({ icon: 'error', title: 'Load Failed', text: err.response?.data?.message || 'Could not fetch order details.' });
    }
  } finally {
    loading.value = false;
  }
};

// ─── Table Modal Handler ──────────────────────────────────────────────────────
const handleTableSelected = async (table) => {
  showTableModal.value = false;
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/table`, { table_id: table.id });
    showToast(`Table ${table.table_code} assigned`);
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) {
      Swal.fire({ icon: 'error', title: 'Assign Table Failed', text: err.response?.data?.message || 'Could not assign table.' });
    }
  }
};

// ─── Waiter / Rider ──────────────────────────────────────────────────────────
const updateWaiter = async () => {
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/waiter`, { waiter_id: selectedWaiterId.value });
    showToast('Waiter updated');
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Update Failed', text: err.response?.data?.message });
    selectedWaiterId.value = order.value.waiter_id;
  }
};

const updateDeliveryExecutive = async () => {
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/delivery-executive`, { delivery_executive_id: selectedDeliveryExecutiveId.value });
    showToast('Rider assigned');
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Rider Update Failed', text: err.response?.data?.message });
    selectedDeliveryExecutiveId.value = order.value.delivery_executive_id;
  }
};

// ─── Delivery Fee ─────────────────────────────────────────────────────────────
const updateDeliveryFee = async () => {
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/delivery-fee`, { delivery_fee: deliveryFeeInput.value });
    showToast('Delivery fee updated');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Delivery Fee Failed', text: err.response?.data?.message });
    deliveryFeeInput.value = order.value.delivery_fee;
  }
};

// ─── Customer ────────────────────────────────────────────────────────────────
const removeCustomer = async () => {
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/customer`, { customer_id: null });
    showToast('Customer removed');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Remove Failed', text: err.response?.data?.message });
  }
};

const triggerAddCustomerModal = () => {
  showAddCustomerModal.value = true;
};

const handleDrawerSaveCustomer = async (customerData) => {
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/customer`, {
      customer_id: customerData?.id || null,
    });
    showToast('Customer attached to order');
    showAddCustomerModal.value = false;
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');

    if (pendingDrawerAction.value) {
      const action = pendingDrawerAction.value;
      pendingDrawerAction.value = null;
      if (action.type === 'payment') {
        await handlePaymentSubmit(action.payload);
      } else if (action.type === 'change_method') {
        await changePaymentMethod(action.paymentId, action.method);
      }
    }
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Update Failed', text: err.response?.data?.message || 'Failed to attach customer to order.' });
  }
};

// ─── Discount ────────────────────────────────────────────────────────────────
const applyDiscount = async () => {
  if (!order.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/discount`, { discount_type: discountTypeInput.value, discount_value: discountValueInput.value });
    showAddDiscount.value = false;
    showToast('Discount applied');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Discount Failed', text: err.response?.data?.message });
  }
};

const removeDiscount = async () => {
  if (!order.value) return;
  try {
    await axios.delete(`/api/pos/orders/${order.value.id}/discount`);
    showToast('Discount removed');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Remove Failed', text: err.response?.data?.message });
  }
};

// ─── Order Status Stepper ─────────────────────────────────────────────────────
const moveToNextStatus = async () => {
  if (!order.value) return;
  const nextIdx = currentStatusIndex.value + 1;
  if (nextIdx >= activeStatusList.value.length) return;
  const nextStatus = activeStatusList.value[nextIdx];
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/status`, { order_status: nextStatus });
    showToast(`Status → ${orderStatusLabel(nextStatus)}`);
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Status Update Failed', text: err.response?.data?.message });
  }
};

// ─── Cancel / Delete ─────────────────────────────────────────────────────────
const triggerCancelOrder = async () => {
  if (!order.value || !window.Swal) return;
  const reasons = cancelReasons.value;
  const { value: formValues } = await Swal.fire({
    title: 'Cancel Order',
    html: `<div class="text-left space-y-3 text-sm">
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Select Cancel Reason</label>
        <select id="swal-reason" class="swal2-select w-full m-0 text-sm">
          <option value="">-- Choose a reason --</option>
          ${reasons.map(r => `<option value="${r.id}">${r.reason}</option>`).join('')}
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Additional Comments</label>
        <textarea id="swal-comment" class="swal2-textarea w-full m-0 text-sm" placeholder="Optional additional note..."></textarea>
      </div>
    </div>`,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Confirm Cancel',
    confirmButtonColor: '#dc2626',
    preConfirm: () => {
      const reasonId = document.getElementById('swal-reason').value;
      const comment = document.getElementById('swal-comment').value;
      if (!reasonId && !comment.trim()) {
        Swal.showValidationMessage('Please select a reason or enter a comment');
        return false;
      }
      return { reasonId, comment };
    },
  });
  if (formValues) {
    try {
      await axios.post(`/api/pos/orders/${order.value.id}/status`, {
        order_status: 'cancelled',
        cancel_reason_id: formValues.reasonId || null,
        cancel_reason_text: formValues.comment || null,
      });
      showToast('Order cancelled');
      await fetchOrderDetails(order.value.id);
      dispatchLivewireEvent('refreshOrders');
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Cancellation Failed', text: err.response?.data?.message });
    }
  }
};

const triggerDeleteOrder = async () => {
  if (!order.value || !window.Swal) return;
  const { isConfirmed } = await Swal.fire({
    title: 'Delete Order',
    text: 'This will permanently delete this order. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, Delete',
  });
  if (isConfirmed) {
    try {
      await axios.delete(`/api/pos/orders/${order.value.id}`);
      showToast('Order deleted');
      closeDrawer();
      dispatchLivewireEvent('refreshOrders');
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Delete Failed', text: err.response?.data?.message });
    }
  }
};

// ─── KOT Item Actions ─────────────────────────────────────────────────────────
const reduceKotItem = async (line) => {
  if (!window.Swal) return;

  if (line.qty <= 1) {
    await deleteKotItem(line);
    return;
  }

  const nextQty = line.qty - 1;
  const { value: reason } = await Swal.fire({
    title: 'Provide a reason for removing or reducing KOT items',
    input: 'text',
    inputLabel: `Reduce quantity of "${line.item_name}" to ${nextQty}`,
    inputPlaceholder: 'Enter reason (min 3 characters)...',
    showCancelButton: true,
    inputValidator: (v) => (!v || v.trim().length < 3) ? 'Please provide a valid reason!' : null,
  });
  if (!reason) return;
  try {
    const res = await axios.patch(`/api/pos/orders/${order.value.id}/kot-items/${line.kot_item_id || 0}/quantity`, {
      new_quantity: nextQty,
      reason,
      order_item_id: line.order_item_id,
    });
    showToast('Quantity reduced');
    if (res.data?.data?.order_cancelled_or_deleted) closeDrawer();
    else await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Adjustment Failed', text: err.response?.data?.message });
  }
};

const deleteKotItem = async (line) => {
  if (!window.Swal) return;
  const { value: reason } = await Swal.fire({
    title: 'Provide a reason for removing or reducing KOT items',
    input: 'text',
    inputLabel: `Remove "${line.item_name}" from the order?`,
    inputPlaceholder: 'Enter reason (min 3 characters)...',
    showCancelButton: true,
    inputValidator: (v) => (!v || v.trim().length < 3) ? 'Please provide a valid reason!' : null,
  });
  if (!reason) return;
  try {
    const res = await axios.delete(`/api/pos/orders/${order.value.id}/kot-items/${line.kot_item_id || 0}`, {
      data: { reason, order_item_id: line.order_item_id },
    });
    showToast('Item removed');
    if (res.data?.data?.order_cancelled_or_deleted) closeDrawer();
    else await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Remove Failed', text: err.response?.data?.message });
  }
};

// ─── Print / Redirect ─────────────────────────────────────────────────────────
const printReceipt = () => {
  if (!order.value) return;
  window.open(`/orders/print/${order.value.id}`, '_blank');
};

const redirectToPos = () => {
  if (!order.value) return;
  window.location.href = `/pos/kot/${order.value.id}`;
};

// ─── Payment ──────────────────────────────────────────────────────────────────
const openPaymentModal = () => { showPaymentModal.value = true; };

const changePaymentMethod = async (paymentId, method) => {
  if (!order.value) return;

  if (method === 'due' && !order.value.customer_id) {
    pendingDrawerAction.value = { type: 'change_method', paymentId, method };
    showAddCustomerModal.value = true;
    return;
  }

  try {
    await axios.post(`/api/pos/orders/${order.value.id}/payments/${paymentId}/method`, { payment_method: method });
    showToast('Payment method updated');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (err.response?.data?.needs_customer) {
      pendingDrawerAction.value = { type: 'change_method', paymentId, method };
      showAddCustomerModal.value = true;
      return;
    }
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Update Failed', text: err.response?.data?.message || 'Failed to update payment method.' });
    await fetchOrderDetails(order.value.id);
  }
};

const handlePaymentSubmit = async (payload) => {
  if (!order.value || paymentSaving.value) return;

  const isDue = payload.payment_method === 'due' || (payload.split_type && payload.splits?.some(s => s.payment_method === 'due'));
  if (isDue && !order.value.customer_id) {
    pendingDrawerAction.value = { type: 'payment', payload };
    showAddCustomerModal.value = true;
    return;
  }

  paymentSaving.value = true;
  try {
    const body = {
      payment_method: payload.payment_method,
      amount: payload.amount,
    };
    if (payload.split_type) { body.split_type = payload.split_type; body.splits = payload.splits; }
    if (payload.room_charge_reservation_id != null) body.room_charge_reservation_id = payload.room_charge_reservation_id;
    await axios.post(`/api/pos/orders/${order.value.id}/pay`, body);
    showPaymentModal.value = false;
    showToast('Payment recorded');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (err.response?.data?.needs_customer) {
      pendingDrawerAction.value = { type: 'payment', payload };
      showAddCustomerModal.value = true;
      return;
    }
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Payment Failed', text: err.response?.data?.message });
  } finally {
    paymentSaving.value = false;
  }
};

// ─── Drawer lifecycle ─────────────────────────────────────────────────────────
const closeDrawer = () => {
  visible.value = false;
  order.value = null;
  orderId.value = null;
  previewData.value = null;
  previewOrderNumber.value = null;
};

// Handles both `showOrderDetail` and `show_order_detail` events.
// When the event carries a rich payload (order-card.blade.php now sends one)
// we build a previewData stub so the header renders in the same tick as the
// drawer opening, with no visible loading state for the skeleton-free parts.
const onShowOrderDetail = (event) => {
  const detail = event.detail;
  // Support both direct object and Livewire-wrapped array formats
  const raw = Array.isArray(detail) ? (detail[0] ?? {}) : (detail ?? {});
  const idRaw = raw.id ?? raw;
  if (!idRaw) return;

  const id = Number(idRaw);
  if (!id) return;

  orderId.value = id;
  visible.value = true;

  // Build a preview stub from whatever data came with the event
  // (order-card.blade now passes order_number, total, status, order_type,
  //  customer_name, table_code). Fields not present fall back to null.
  previewData.value = {
    id,
    formatted_order_number: raw.order_number ?? previewOrderNumber.value ?? `#${id}`,
    order_number: raw.order_number ?? String(id),
    total: raw.total ?? null,
    status: raw.status ?? '',
    order_type: raw.order_type ?? 'dine_in',
    customer: raw.customer_name ? { name: raw.customer_name } : null,
    table_code: raw.table_code ?? null,
  };
  previewOrderNumber.value = raw.order_number ?? String(id);

  // Full data fetch — fills in lines, KOTs, permissions, etc.
  void fetchOrderDetails(id);
};

onMounted(() => {
  window.addEventListener('showOrderDetail', onShowOrderDetail);
  window.addEventListener('show_order_detail', onShowOrderDetail);

  // Track dark mode mutations
  const observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark');
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
  window.removeEventListener('showOrderDetail', onShowOrderDetail);
  window.removeEventListener('show_order_detail', onShowOrderDetail);
});
</script>

<style scoped>
[v-cloak] { display: none !important; }
</style>
