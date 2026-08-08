<template>
  <div v-cloak>
    <div
      v-if="visible"
      class="fixed inset-0 bg-black/50 backdrop-blur-[1px] transition-opacity duration-300"
      style="z-index: 40;"
      @click="closeDrawer"
    ></div>

    <div
      v-if="visible"
      class="fixed inset-y-0 right-0 flex"
      style="z-index: 45;"
    >
      <div class="relative w-screen max-w-xl bg-white dark:bg-gray-800 shadow-2xl flex flex-col h-full border-l border-gray-200 dark:border-gray-700">

        <div v-if="!order && !previewData && !loading" class="flex-1 flex flex-col items-center justify-center p-8 text-gray-500 dark:text-gray-400">
          <span class="text-sm font-medium">No order found.</span>
        </div>

        <div v-else-if="loading && !order && !previewData" class="flex-1 flex flex-col items-center justify-center p-8 text-gray-500 dark:text-gray-400">
          <svg class="animate-spin h-7 w-7 text-indigo-500 mb-3" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-sm font-medium">Loading...</span>
        </div>

        <template v-else-if="order || previewData">
          <div v-if="loading" class="absolute top-0 left-0 right-0 h-0.5 bg-indigo-200 dark:bg-indigo-900 overflow-hidden" style="z-index: 46;">
            <div class="h-full bg-indigo-500 animate-pulse" style="width: 60%;"></div>
          </div>

          <div class="flex-1 overflow-y-auto p-5 space-y-5">
            <div v-if="perms.read_only_cross_branch" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
              This order belongs to another branch and is read-only.
            </div>

            <div class="flex justify-between items-start gap-3">
              <div class="flex-1 min-w-0 space-y-2">
                <div class="flex items-center gap-2 min-w-0">
                  <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight truncate">
                    {{ displayOrder.formatted_order_number || ('Order #' + displayOrder.order_number) }}
                  </h2>
                  <span v-if="displayOrder.token_number" class="shrink-0 text-[10px] font-semibold text-gray-600 dark:text-gray-300">
                    Token {{ displayOrder.token_number }}
                  </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                  <button
                    v-if="displayOrder.order_type === 'dine_in'"
                    @click="showTableModal = true"
                    :disabled="!perms.can_update_order"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm disabled:opacity-50"
                  >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                    <span>{{ displayOrder.table_code || 'Assign Table' }}</span>
                  </button>

                  <div v-if="displayOrder.hotel_reservation?.room_number" class="space-y-1">
                    <div class="text-sm font-semibold text-gray-800 dark:text-white">
                      Room {{ displayOrder.hotel_reservation.room_number }}
                      <span class="text-xs font-normal text-gray-500">({{ displayOrder.hotel_reservation.guest_name }})</span>
                    </div>
                    <span
                      v-if="displayOrder.folio_badge"
                      class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide"
                      :class="displayOrder.folio_badge.tone === 'settled'
                        ? 'bg-teal-100 text-teal-800 border border-teal-300 dark:bg-teal-900/40 dark:text-teal-200'
                        : 'bg-violet-100 text-violet-800 border border-violet-300 dark:bg-violet-900/40 dark:text-violet-200'"
                    >{{ displayOrder.folio_badge.label }}</span>
                    <a
                      v-if="perms.can_view_hotel_folio && displayOrder.hotel_reservation.folio_url"
                      :href="displayOrder.hotel_reservation.folio_url"
                      class="inline-flex items-center gap-1 text-xs font-medium text-violet-700 hover:text-violet-900 dark:text-violet-300"
                    >View Guest Folio</a>
                  </div>

                  <div v-else class="flex items-center gap-1">
                    <span v-if="displayOrder.customer?.name" class="text-sm font-semibold text-gray-800 dark:text-white truncate max-w-[120px]" :title="displayOrder.customer.name">
                      {{ displayOrder.customer.name }}
                    </span>
                    <button
                      v-else-if="perms.can_update_order"
                      @click="triggerAddCustomerModal"
                      class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                      Add Customer
                    </button>

                    <button
                      v-if="perms.can_update_order"
                      @click="triggerAddCustomerModal"
                      class="p-1 rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                      title="Edit Customer"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>

                    <button
                      v-if="displayOrder.customer_id && perms.can_update_order"
                      @click="removeCustomer"
                      class="p-1 rounded border border-gray-200 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-colors"
                      title="Remove Customer"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>

                <div class="text-[10px] text-gray-400 dark:text-gray-500">
                  <span v-if="displayOrder.order_type === 'pickup' && displayOrder.pickup_date">Pickup {{ formatDateTime(displayOrder.pickup_date) }}</span>
                  <span v-else>{{ formatDateTime(displayOrder.date_time || displayOrder.created_at) }}</span>
                </div>

                <div v-if="displayOrder.delivery_platform" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                  <img v-if="displayOrder.delivery_platform.logo_url" :src="displayOrder.delivery_platform.logo_url" class="w-4 h-4 rounded" alt="" />
                  {{ displayOrder.delivery_platform.name }}
                </div>

                <div class="flex flex-wrap gap-2 pt-0.5">
                  <div class="relative">
                    <select
                      v-model="selectedWaiterId"
                      @change="updateWaiter"
                      :disabled="!perms.can_assign_waiter"
                      class="pl-3 pr-7 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 focus:outline-none cursor-pointer appearance-none shadow-sm disabled:opacity-50 max-w-[160px]"
                    >
                      <option :value="null">Select Waiter</option>
                      <option v-for="w in waiters" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                    <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-gray-400">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                  </div>

                  <div v-if="displayOrder.order_type === 'delivery'" class="relative">
                    <select
                      v-model="selectedDeliveryExecutiveId"
                      @change="updateDeliveryExecutive"
                      :disabled="!perms.can_update_order"
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

              <div class="flex flex-col items-end space-y-2 flex-shrink-0">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-300 capitalize">
                  {{ (displayOrder.order_type_name || displayOrder.order_type || '').replace(/_/g, ' ') }}
                </span>

                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide"
                  :class="{
                    'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800': displayOrder.status === 'kot',
                    'bg-yellow-50 text-yellow-700 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800': displayOrder.status === 'billed',
                    'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800': displayOrder.status === 'paid',
                    'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800': displayOrder.status === 'canceled',
                    'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800': displayOrder.status === 'payment_due',
                    'bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-900/30 dark:text-teal-300': displayOrder.status === 'folio_settled',
                    'bg-orange-50 text-orange-700 border border-orange-200': displayOrder.status === 'pending_verification',
                    'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600': !['kot','billed','paid','canceled','payment_due','folio_settled','pending_verification'].includes(displayOrder.status),
                  }"
                >{{ displayOrder.status }}</span>

                <span
                  v-if="displayOrder.folio_badge"
                  class="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide"
                  :class="displayOrder.folio_badge.tone === 'settled'
                    ? 'bg-teal-50 text-teal-700 border border-teal-200'
                    : 'bg-violet-50 text-violet-700 border border-violet-200'"
                >{{ displayOrder.folio_badge.label }}</span>

                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-900/30 dark:text-violet-300 dark:border-violet-800">
                  {{ displayOrder.placed_via || 'POS' }}
                </span>
              </div>
            </div>

            <div v-if="displayOrder.status === 'canceled' || displayOrder.order_status === 'cancelled'" class="p-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 text-sm">
              <div class="font-semibold text-red-800 dark:text-red-200">Order cancelled</div>
              <div v-if="displayOrder.cancel_reason" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ displayOrder.cancel_reason }}</div>
              <div v-if="displayOrder.cancel_reason_text" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ displayOrder.cancel_reason_text }}</div>
            </div>

            <div v-else class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
              <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Order Status</h3>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 uppercase">
                  {{ orderStatusLabel(displayOrder.order_status) }}
                </span>
              </div>

              <div class="flex justify-between items-start relative">
                <div v-for="(status, i) in activeStatusList" :key="status" class="flex flex-col items-center flex-1 relative">
                  <div v-if="i < activeStatusList.length - 1"
                    class="absolute top-3.5 left-1/2 w-full h-0.5 z-0"
                    :class="i < currentStatusIndex ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-600'"
                  ></div>
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

              <div v-if="perms.can_update_order && currentStatusIndex < activeStatusList.length - 1" class="flex justify-end pt-1">
                <button @click="moveToNextStatus"
                  class="inline-flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-bold rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm"
                >
                  Move to {{ orderStatusLabel(activeStatusList[currentStatusIndex + 1]) }}
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
              </div>
            </div>

            <div v-if="displayOrder.note" class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/40 rounded-lg px-3 py-2">
              <span class="font-semibold">Note:</span> {{ displayOrder.note }}
            </div>

            <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
              <div class="flex text-[10px] font-bold uppercase tracking-wider px-2 py-2 rounded mb-1"
                :style="isDark ? 'background:#374151;color:#9ca3af' : 'background:#f3f4f6;color:#6b7280'"
              >
                <div class="flex-1 min-w-0 pr-2">Item Name</div>
                <div class="w-16 text-center flex-shrink-0">Qty</div>
                <div class="w-20 text-right flex-shrink-0">Price</div>
                <div class="w-20 text-right flex-shrink-0">Amount</div>
                <div class="w-8 flex-shrink-0"></div>
              </div>

              <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <template v-for="group in lineGroups" :key="group.key">
                  <div v-if="group.isCombo" class="flex items-center justify-between px-2 py-1.5 bg-indigo-50/70 dark:bg-indigo-900/20">
                    <div class="text-[11px] font-bold text-indigo-800 dark:text-indigo-200">
                      {{ group.name || 'Combo Pack' }}
                      <span v-if="group.savings > 0" class="ml-1 font-semibold text-green-600">Save {{ formatCurrency(group.savings) }}</span>
                    </div>
                    <button
                      v-if="canManageItems"
                      @click="removeComboGroup(group)"
                      class="text-[10px] font-semibold text-red-600 hover:underline"
                    >Remove combo</button>
                  </div>

                  <div v-for="line in group.lines" :key="line.order_item_id || line.kot_item_id" class="flex items-start px-2 py-2.5 gap-1 text-xs">
                    <div class="flex-1 min-w-0 pr-2">
                      <div class="font-semibold text-gray-800 dark:text-white leading-snug truncate" :title="line.item_name">{{ line.item_name }}</div>
                      <div v-if="line.variation_name" class="text-[9px] text-gray-400 mt-0.5">Var: {{ line.variation_name }}</div>
                      <div v-if="displayLineNote(line)" class="text-[10px] text-gray-500 mt-0.5 italic">{{ displayLineNote(line) }}</div>
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

                    <div class="w-16 flex-shrink-0 flex justify-center items-start pt-0.5">
                      <div class="inline-flex items-center border border-gray-300 dark:border-gray-600 rounded overflow-hidden">
                        <button
                          @click="reduceKotItem(line)"
                          :disabled="!canManageItems"
                          class="w-5 h-6 flex items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50 hover:bg-gray-100 dark:bg-gray-600/50 dark:hover:bg-gray-600 font-bold text-sm leading-none disabled:opacity-30 select-none"
                        >−</button>
                        <span class="w-6 h-6 flex items-center justify-center text-[11px] font-bold text-gray-800 dark:text-gray-200 select-none">{{ line.qty }}</span>
                      </div>
                    </div>

                    <div class="w-20 flex-shrink-0 text-right pt-0.5">
                      <span class="text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap">{{ formatCurrency(line.unit_price) }}</span>
                    </div>

                    <div class="w-20 flex-shrink-0 text-right pt-0.5">
                      <span class="font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ formatCurrency(line.amount) }}</span>
                    </div>

                    <div class="w-8 flex-shrink-0 flex justify-end items-start pt-0.5">
                      <button
                        v-if="canManageItems && !group.isCombo"
                        @click="deleteKotItem(line)"
                        class="w-6 h-6 flex items-center justify-center rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-colors shadow-sm"
                        title="Remove item"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </div>
                </template>

                <div v-if="!displayOrder.lines?.length" class="py-6 text-center text-xs text-gray-400">No items found.</div>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-700/60 p-4 space-y-2 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>Item(s)</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ displayOrder.lines?.length || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span>Sub Total</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ formatCurrency(displayOrder.sub_total) }}</span>
              </div>

              <div v-if="displayOrder.order_type === 'delivery'" class="flex justify-between items-center">
                <span>Delivery Fee</span>
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] text-gray-400">Edit:</span>
                  <input
                    type="number" step="0.01" min="0"
                    v-model.number="deliveryFeeInput"
                    @blur="updateDeliveryFee"
                    @keyup.enter="$event.target.blur()"
                    :disabled="!perms.can_update_order"
                    class="w-16 px-1.5 py-0.5 text-right text-xs border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded text-gray-800 dark:text-white font-medium disabled:opacity-50"
                  />
                </div>
              </div>

              <div v-if="displayOrder.discount_amount > 0" class="flex justify-between items-center text-red-500 dark:text-red-400">
                <span class="flex items-center gap-1">
                  Discount ({{ displayOrder.discount_type === 'percent' ? displayOrder.discount_value + '%' : 'Fixed' }})
                  <button v-if="canDiscount" @click="removeDiscount" class="font-bold text-red-400 hover:text-red-600 leading-none">×</button>
                </span>
                <span>-{{ formatCurrency(displayOrder.discount_amount) }}</span>
              </div>

              <div v-if="displayOrder.reward_point_discount > 0" class="flex justify-between text-amber-600 dark:text-amber-400">
                <span>Reward discount ({{ displayOrder.reward_points_redeemed }} pts)</span>
                <span>-{{ formatCurrency(displayOrder.reward_point_discount) }}</span>
              </div>

              <div
                v-for="(extra, extraIndex) in displayOrder.custom_extras"
                :key="extra.id || `extra-${extraIndex}`"
                class="flex justify-between text-gray-500 italic"
              >
                <span>+ {{ extra.note || 'Extra' }}</span>
                <span>{{ formatCurrency(extra.amount) }}</span>
              </div>

              <div v-for="charge in displayOrder.charges" :key="charge.id" class="flex justify-between items-center">
                <span class="inline-flex items-center gap-1">
                  {{ charge.name }}
                  <span v-if="charge.charge_type === 'percent'">({{ charge.charge_value }}%)</span>
                  <button
                    v-if="canRemoveCharge"
                    @click="removeCharge(charge.id)"
                    class="text-red-400 hover:text-red-600 font-bold leading-none"
                  >×</button>
                </span>
                <span>{{ formatCurrency(charge.amount) }}</span>
              </div>

              <div v-if="displayOrder.tip_amount > 0" class="flex justify-between">
                <span>Tip</span>
                <span>{{ formatCurrency(displayOrder.tip_amount) }}</span>
              </div>

              <div v-for="tax in displayOrder.taxes" :key="tax.name" class="flex justify-between">
                <span>{{ tax.name }}<span v-if="tax.percent"> ({{ tax.percent }}%)</span></span>
                <span>{{ formatCurrency(tax.amount) }}</span>
              </div>

              <div class="flex justify-between font-bold text-sm text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-2">
                <span>Total</span>
                <span>{{ formatCurrency(displayOrder.total) }}</span>
              </div>
              <div v-if="displayOrder.amount_paid > 0" class="flex justify-between text-green-600 dark:text-green-400 font-semibold text-xs">
                <span>Amount Paid</span>
                <span>{{ formatCurrency(displayOrder.amount_paid) }}</span>
              </div>
              <div v-if="displayOrder.due_amount > 0" class="flex justify-between text-red-600 dark:text-red-400 font-semibold text-xs">
                <span>Due Amount</span>
                <span>{{ formatCurrency(displayOrder.due_amount) }}</span>
              </div>
              <div v-if="(displayOrder.balance_returned || 0) > 0.0001" class="flex justify-between text-gray-400 text-xs">
                <span>Balance Returned</span>
                <span>{{ formatCurrency(displayOrder.balance_returned) }}</span>
              </div>
              <div v-if="displayOrder.reward_points_earned > 0" class="flex justify-between text-amber-500 pt-1 border-t border-dashed border-gray-200 dark:border-gray-600">
                <span>Points awarded</span>
                <span>+{{ displayOrder.reward_points_earned }} pts</span>
              </div>
            </div>

            <div v-if="displayOrder.order_type === 'delivery' && (displayOrder.delivery_address || displayOrder.customer_phone)" class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 p-3 text-xs space-y-2">
              <div class="flex items-center justify-between">
                <span class="font-semibold text-gray-800 dark:text-gray-200">Delivery address</span>
                <a
                  v-if="displayOrder.customer_lat && displayOrder.customer_lng && displayOrder.branch_lat && displayOrder.branch_lng"
                  :href="`https://www.google.com/maps/dir/?api=1&travelmode=two-wheeler&origin=${displayOrder.branch_lat},${displayOrder.branch_lng}&destination=${displayOrder.customer_lat},${displayOrder.customer_lng}`"
                  target="_blank"
                  class="text-blue-500 hover:underline"
                >View on map</a>
              </div>
              <div v-if="displayOrder.delivery_address" class="text-gray-600 dark:text-gray-300">{{ displayOrder.delivery_address }}</div>
              <a v-if="displayOrder.customer_phone" :href="`tel:${String(displayOrder.customer_phone).replace(/\s+/g, '')}`" class="inline-flex text-blue-600 hover:underline">
                {{ displayOrder.customer_phone }}
              </a>
            </div>

            <div v-if="displayOrder.payments && displayOrder.payments.length" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden space-y-0 text-xs">
              <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-[11px] flex justify-between items-center">
                <span>Payments Recorded</span>
                <span>{{ displayOrder.payments.length }}</span>
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
                  <tr v-for="pay in displayOrder.payments" :key="pay.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-2 font-medium text-gray-900 dark:text-gray-200">
                      {{ formatCurrency(pay.amount) }}
                    </td>
                    <td class="p-2 text-center">
                      <select
                        v-if="perms.can_update_order && displayOrder.status !== 'pending_verification'"
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
                      <template v-if="pay.payment_method === 'due' && canAddPayment">
                        <button @click="openPaymentModal" class="text-indigo-600 font-semibold hover:underline">Add Payment</button>
                      </template>
                      <template v-else-if="displayOrder.status === 'pending_verification' && perms.can_update_order">
                        <button @click="verifyPendingPayment('received')" class="text-green-600 font-semibold hover:underline mr-1">Confirm</button>
                        <button @click="verifyPendingPayment('not_received')" class="text-red-600 font-semibold hover:underline">Unpaid</button>
                      </template>
                      <template v-else>
                        {{ pay.formatted_date || formatDateTime(pay.created_at) }}
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="space-y-2">
              <div v-if="canAddKotItems || canBillKot" class="grid grid-cols-2 gap-2">
                <button
                  v-if="canAddKotItems"
                  @click="redirectToPos"
                  class="py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 text-white bg-gray-700 hover:bg-gray-800"
                >Add Items</button>
                <button
                  v-if="canBillKot"
                  @click="billKotOrder"
                  class="py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 text-white bg-green-600 hover:bg-green-700"
                >Bill</button>
              </div>

              <button
                v-if="canAddPayment"
                @click="openPaymentModal"
                class="w-full py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 transition-colors shadow"
                style="background-color: #047857; color: #fff;"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Add Payment
              </button>

              <button
                v-if="canNewKot"
                @click="redirectToPos"
                class="w-full py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 transition-colors shadow"
                style="background-color: #1e293b; color: #fff;"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New KOT
              </button>

              <button
                v-if="canDiscount && !showAddDiscount"
                @click="showAddDiscount = true"
                class="w-full py-2.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors border"
                :style="isDark ? 'background:#374151;color:#e5e7eb;border-color:#4b5563' : 'background:#fff;color:#374151;border-color:#d1d5db'"
              >
                Add Discount
              </button>

              <div v-if="showAddDiscount && canDiscount" class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
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

          <div class="p-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-700 grid grid-cols-4 gap-2 flex-shrink-0">
            <button @click="printReceipt()"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors border"
              :style="isDark ? 'background:#374151;color:#fff;border-color:#4b5563' : 'background:#fff;color:#374151;border-color:#d1d5db'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              Print
            </button>

            <button
              v-if="canCancel"
              @click="triggerCancelOrder"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors"
              style="background:#dc2626;color:#fff;"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Cancel
            </button>

            <button
              v-if="canDelete"
              @click="triggerDeleteOrder"
              class="py-3 rounded-lg text-[10px] font-bold flex flex-col items-center justify-center gap-1 uppercase transition-colors"
              style="background:#ef4444;color:#fff;"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Delete
            </button>

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

    <TableAssignmentModal
      v-if="showTableModal"
      :show="showTableModal"
      @close="showTableModal = false"
      @select="handleTableSelected"
    />

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

const visible = ref(false);
const loading = ref(false);
const orderId = ref(null);
const order = ref(null);
const previewData = ref(null);
const previewOrderNumber = ref(null);
const fromReport = ref(false);

const isDark = ref(document.documentElement.classList.contains('dark'));

const waiters = ref([]);
const deliveryExecutives = ref([]);
const cancelReasons = ref([]);

const selectedWaiterId = ref(null);
const selectedDeliveryExecutiveId = ref(null);
const deliveryFeeInput = ref(0);

const showAddDiscount = ref(false);
const discountTypeInput = ref('percent');
const discountValueInput = ref(null);

const showTableModal = ref(false);
const showPaymentModal = ref(false);
const paymentSaving = ref(false);
const showAddCustomerModal = ref(false);
const pendingDrawerAction = ref(null);

const emptyPermissions = () => ({
  can_update_order: false,
  can_delete_order: false,
  can_edit_billed_order: false,
  can_delete_kot_item: false,
  can_manage_items: false,
  can_assign_waiter: false,
  can_view_hotel_folio: false,
  read_only_cross_branch: false,
});

const displayOrder = computed(() => {
  if (order.value) {
    return order.value;
  }
  const preview = previewData.value || {};
  return {
    ...preview,
    permissions: emptyPermissions(),
    lines: [],
    payments: [],
    custom_extras: [],
    taxes: [],
    charges: [],
    status: preview.status || '',
    order_type: preview.order_type || 'dine_in',
  };
});

const perms = computed(() => ({
  ...emptyPermissions(),
  ...(displayOrder.value?.permissions || {}),
}));

const billedLifecycle = computed(() => ['billed', 'paid', 'payment_due'].includes(displayOrder.value?.status));
const canAddPayment = computed(() => perms.value.can_update_order && ['billed', 'payment_due'].includes(displayOrder.value?.status));
const canNewKot = computed(() => perms.value.can_edit_billed_order && billedLifecycle.value);
const canAddKotItems = computed(() =>
  !perms.value.read_only_cross_branch
  && !displayOrder.value?.folio_locked
  && displayOrder.value?.status === 'kot'
  && !!displayOrder.value?.table_id
);
const canBillKot = computed(() => canAddKotItems.value);
const canDiscount = computed(() => perms.value.can_edit_billed_order && billedLifecycle.value);
const canCancel = computed(() => perms.value.can_delete_order && ['billed', 'payment_due', 'paid'].includes(displayOrder.value?.status));
const canDelete = computed(() => perms.value.can_delete_order && !['paid', 'payment_due', 'canceled'].includes(displayOrder.value?.status));
const canManageItems = computed(() => !!perms.value.can_manage_items);
const canRemoveCharge = computed(() => {
  const status = displayOrder.value?.status;
  if (status === 'canceled' || perms.value.read_only_cross_branch || displayOrder.value?.folio_locked) {
    return false;
  }
  if (['paid', 'payment_due'].includes(status)) {
    return !!perms.value.can_edit_billed_order;
  }
  return !!perms.value.can_update_order;
});

const lineGroups = computed(() => {
  const lines = displayOrder.value?.lines || [];
  const map = new Map();
  const keys = [];
  for (const line of lines) {
    let key = `line:${line.order_item_id || line.kot_item_id}`;
    if (line.combo_instance_key) {
      key = `instance:${line.combo_instance_key}`;
    } else if (line.combo_pack_id) {
      key = `pack:${line.combo_pack_id}`;
    }
    if (!map.has(key)) {
      map.set(key, {
        key,
        isCombo: !!(line.combo_pack_id || line.combo_instance_key),
        name: line.combo_pack_name,
        lines: [],
        savings: 0,
      });
      keys.push(key);
    }
    const group = map.get(key);
    group.lines.push(line);
    const qty = Number(line.qty || 1);
    group.savings += Number(line.combo_discount || 0) * qty;
    if (!group.name && line.combo_pack_name) {
      group.name = line.combo_pack_name;
    }
  }
  return keys.map((key) => map.get(key));
});

const activeStatusList = computed(() => {
  const type = displayOrder.value?.order_type || 'dine_in';
  if (type === 'delivery') return ['placed', 'confirmed', 'preparing', 'food_ready', 'out_for_delivery', 'delivered'];
  if (type === 'pickup')   return ['placed', 'confirmed', 'preparing', 'food_ready', 'ready_for_pickup', 'delivered'];
  return ['placed', 'confirmed', 'preparing', 'food_ready', 'served'];
});

const currentStatusIndex = computed(() => {
  const idx = activeStatusList.value.indexOf(displayOrder.value?.order_status);
  return idx >= 0 ? idx : 0;
});

const orderStatusLabel = (s) => ({
  placed: 'Placed', confirmed: 'Confirmed', preparing: 'Preparing', food_ready: 'Ready',
  out_for_delivery: 'Out For Delivery', ready_for_pickup: 'Pickup Ready',
  delivered: 'Delivered', served: 'Served', cancelled: 'Cancelled',
}[s] || s);

const formatDateTime = (dt) => {
  if (!dt) return '';
  return new Date(dt).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
};

const formatCurrency = (val) => {
  const sym = displayOrder.value?.currency_symbol || 'Rs';
  return `${sym}${Number(val || 0).toFixed(2)}`;
};

const displayLineNote = (line) => {
  const note = String(line?.note || '').replace(/\[COMBO_INSTANCE:[^\]]+\]/g, '').trim();
  return note;
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

const openOrderPrint = (printUrl, { preparePlaceholder = false } = {}) => {
  const url = printUrl || (order.value ? `/orders/print/${order.value.id}` : null);
  if (!url) {
    return false;
  }
  if (preparePlaceholder && typeof window.preparePosPrintPlaceholder === 'function') {
    window.preparePosPrintPlaceholder();
  }
  if (typeof window.openPosPrintTab === 'function') {
    return window.openPosPrintTab(url);
  }
  window.open(url, '_blank', 'noopener');
  return true;
};

const fetchOrderDetails = async (id) => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/pos/orders/${id}`, {
      params: fromReport.value ? { from_report: 1 } : {},
    });
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

const handleTableSelected = async (table) => {
  showTableModal.value = false;
  if (!order.value || !perms.value.can_update_order) return;
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

const updateWaiter = async () => {
  if (!order.value || !perms.value.can_assign_waiter) return;
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
  if (!order.value || !perms.value.can_update_order) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/delivery-executive`, { delivery_executive_id: selectedDeliveryExecutiveId.value });
    showToast('Rider assigned');
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Rider Update Failed', text: err.response?.data?.message });
    selectedDeliveryExecutiveId.value = order.value.delivery_executive_id;
  }
};

const updateDeliveryFee = async () => {
  if (!order.value || !perms.value.can_update_order) return;
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

const removeCustomer = async () => {
  if (!order.value || !perms.value.can_update_order) return;
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
  if (!perms.value.can_update_order) return;
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

const applyDiscount = async () => {
  if (!order.value || !canDiscount.value) return;
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
  if (!order.value || !canDiscount.value) return;
  try {
    await axios.delete(`/api/pos/orders/${order.value.id}/discount`);
    showToast('Discount removed');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Remove Failed', text: err.response?.data?.message });
  }
};

const removeCharge = async (chargeId) => {
  if (!order.value || !canRemoveCharge.value) return;
  try {
    await axios.delete(`/api/pos/orders/${order.value.id}/charges/${chargeId}`);
    showToast('Charge removed');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Remove Failed', text: err.response?.data?.message });
  }
};

const moveToNextStatus = async () => {
  if (!order.value || !perms.value.can_update_order) return;
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

const triggerCancelOrder = async () => {
  if (!order.value || !canCancel.value || !window.Swal) return;
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
  if (!order.value || !canDelete.value || !window.Swal) return;
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

const reduceKotItem = async (line) => {
  if (!canManageItems.value || !window.Swal) return;

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
  if (!canManageItems.value || !window.Swal) return;
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

const removeComboGroup = async (group) => {
  if (!canManageItems.value || !window.Swal || !group?.lines?.length) return;
  const { value: reason } = await Swal.fire({
    title: 'Remove whole combo',
    input: 'text',
    inputLabel: `Remove "${group.name || 'Combo Pack'}" from the order?`,
    inputPlaceholder: 'Enter reason (min 3 characters)...',
    showCancelButton: true,
    inputValidator: (v) => (!v || v.trim().length < 3) ? 'Please provide a valid reason!' : null,
  });
  if (!reason) return;
  try {
    let cancelled = false;
    for (const line of group.lines) {
      const res = await axios.delete(`/api/pos/orders/${order.value.id}/kot-items/${line.kot_item_id || 0}`, {
        data: { reason, order_item_id: line.order_item_id },
      });
      if (res.data?.data?.order_cancelled_or_deleted) {
        cancelled = true;
        break;
      }
    }
    showToast('Combo removed');
    if (cancelled) closeDrawer();
    else await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Remove Failed', text: err.response?.data?.message });
  }
};

const printReceipt = () => {
  if (!order.value) return;
  openOrderPrint(`/orders/print/${order.value.id}`, { preparePlaceholder: true });
};

const redirectToPos = () => {
  if (!order.value) return;
  window.location.href = `/pos/kot/${order.value.id}`;
};

const billKotOrder = async () => {
  if (!order.value || !canBillKot.value) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/bill`);
    showToast('Order billed');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Bill Failed', text: err.response?.data?.message });
  }
};

const verifyPendingPayment = async (result) => {
  if (!order.value || !perms.value.can_update_order) return;
  try {
    await axios.post(`/api/pos/orders/${order.value.id}/payment-verification`, { result });
    showToast('Payment status updated');
    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Update Failed', text: err.response?.data?.message });
  }
};

const openPaymentModal = () => {
  if (!canAddPayment.value) return;
  showPaymentModal.value = true;
};

const changePaymentMethod = async (paymentId, method) => {
  if (!order.value || !perms.value.can_update_order) return;

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

  if (order.value.direct_print_after_payment && typeof window.preparePosPrintPlaceholder === 'function') {
    window.preparePosPrintPlaceholder();
  }

  paymentSaving.value = true;
  try {
    const body = {
      payment_method: payload.payment_method,
      amount: payload.amount,
    };
    if (payload.split_type) { body.split_type = payload.split_type; body.splits = payload.splits; }
    if (payload.room_charge_reservation_id != null) body.room_charge_reservation_id = payload.room_charge_reservation_id;
    const res = await axios.post(`/api/pos/orders/${order.value.id}/pay`, body);
    const data = res.data?.data || {};
    showPaymentModal.value = false;
    showToast('Payment recorded');

    if (data.direct_print && data.print_url) {
      openOrderPrint(data.print_url);
    } else if (typeof window.closePosPrintPlaceholder === 'function') {
      window.closePosPrintPlaceholder();
    }

    await fetchOrderDetails(order.value.id);
    dispatchLivewireEvent('refreshOrders');
  } catch (err) {
    if (typeof window.closePosPrintPlaceholder === 'function') {
      window.closePosPrintPlaceholder();
    }
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

const closeDrawer = () => {
  visible.value = false;
  order.value = null;
  orderId.value = null;
  previewData.value = null;
  previewOrderNumber.value = null;
  fromReport.value = false;
  showAddDiscount.value = false;
};

const onShowOrderDetail = (event) => {
  const detail = event.detail;
  const raw = Array.isArray(detail) ? (detail[0] ?? {}) : (detail ?? {});
  const idRaw = raw.id ?? raw;
  if (!idRaw) return;

  const id = Number(idRaw);
  if (!id) return;

  orderId.value = id;
  visible.value = true;
  fromReport.value = !!(raw.fromReport || raw.from_report);

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

  void fetchOrderDetails(id);
};

onMounted(() => {
  window.addEventListener('showOrderDetail', onShowOrderDetail);
  window.addEventListener('show_order_detail', onShowOrderDetail);

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
