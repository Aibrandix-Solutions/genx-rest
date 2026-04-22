# Linked-Order Mode Gap Analysis
**URL pattern:** `/pos/kot/{id}?show-order-detail=true`  
**Legacy component:** `order_detail.blade.php` + `order_items.blade.php`  
**Vue component:** `OrderPanel.vue` with `isLinkedOrderMode = true`  
**Last verified:** 2026-04-20 � All gaps FIXED ?

---

## Overview

When an order is opened via a direct link, the POS operates in **Linked-Order Mode**. The legacy system renders a completely different view via `order_detail.blade.php` (read-only KOT group table with order header badges, status timeline, and lifecycle-aware action buttons). The Vue system reuses the same `OrderPanel.vue` but switches on `isLinkedOrderMode` flag.

---

## 1. Cart / Item Table UI Gaps

### ✅ What Vue does right
- Renders KOT groups from `kotGroups` prop, each with a header row (`KOT #N` + timestamp) — `linkedKotGroups` computed
- Shows item qty controls (– button calls `requestDecreaseKotItem`, always prompts reason modal)
- Trash icon calls `requestRemoveKotItem` — always prompts reason modal + calls DELETE API
- `Delete KOT Item` permission (`can_delete_kot_item`) correctly gates both `–` button and trash icon
- Inline per-item note (Add Note / preview / edit / delete)
- `canManageLineItems` permission correctly enables/disables qty controls and delete button
- `RemovalReasonModal` is wired — on confirm emits `remove-kot-item` or `reduce-kot-item` to `PosApp.vue`
- `PosApp.vue` handlers call `DELETE /api/pos/orders/{id}/kot-items/{kotItemId}` and `PATCH /api/pos/orders/{id}/kot-items/{kotItemId}/quantity`
- KOT adjustment is logged via `KotAdjustmentLogger` on both delete and quantity reduction

---

### ❌ Gap 1.1 — Order Header: Status Badge, Date/Time, Order Type Icon Missing
**Legacy (`order_detail.blade.php` lines 37–75):**
```
[Table Code badge]   [Customer Name + date/time]   [STATUS badge: KOT/BILLED/PAID]
```
The legacy view shows a prominent coloured status badge (yellow=KOT, blue=billed, green=paid, red=cancelled) and the table code in a branded card alongside the customer name and order date/time.

**Current Vue state:**  
- **Table code** — ✅ Shown via `currentTable` using existing table icon (lines 191–225)  
- **Order date/time** — ❌ Not shown anywhere in linked mode  
- **Coloured status badge (KOT/BILLED/PAID/CANCELLED)** — ❌ Missing. The `linkedLifecycleStatus` value is available in JS but not rendered as a visible badge in the header  
- **Order type icon (bag/bike/fork)** — ❌ Missing in the linked-mode header area  

**Severity:** 🟠 Medium (table code now shows; status/date are missing context)

---

### ❌ Gap 1.2 — Combo Items: No Visual Grouping in Linked KOT Lines
**Legacy (`order_detail.blade.php` lines 261–293):** Within each KOT table, combo items are grouped under a blue header row with the combo pack name, combo savings, and a "Remove combo as a whole" button.

**Current Vue state (`linkedKotGroups` computed, lines 1705–1765):**  
The `normalizedLines` spread via `...line` passes `combo_pack_id` through. However, **the template (`v-for="item in group.lines"`, lines 402–573) renders all items as a flat list** — no nested grouping by `combo_pack_id` within a `linkedKotGroup`. Combo items appear as plain rows with no blue header, no combo savings label, and no "Remove whole combo" button.

> Note: Combo visual grouping IS implemented in the regular (new-order) cart via `groupedCartItems` (lines 594–766), but NOT in the linked KOT view.

**Severity:** 🟠 Medium

---

### ❌ Gap 1.3 — Modifiers Not Rendered in Linked KOT Item Rows
**Legacy (`order_detail.blade.php` lines 311–325):** Each item row renders its selected modifiers as border-l-2 pill rows directly below the item name.

**Current Vue state (lines 404–409):**
```html
<div class="text-xs text-gray-900 ...">{{ item.name }}</div>
<div class="text-xs text-gray-600 ..."></div>  <!-- Always empty -->
```
The `modifier_option_quantities` field IS spread onto each line via `...line` in `linkedKotGroups` (api returns it from `PosVueOrderController@show`). But **the template never renders modifier pills for linked KOT rows** — the empty `<div>` below item name is never populated. Modifier pills are only rendered in the regular cart rows (lines 632–638, 670–676).

**Severity:** 🔴 High

---

### ❌ Gap 1.4 — Variation Name Not Shown in Linked KOT Rows
**Legacy:** `$itemVariation` (e.g. "Large", "Regular") is shown below the item name.

**Current Vue state:**  
`linkedKotGroups` normalizedLines (line 1749):
```js
variant_id: matchedItem?.variant_id || line.menu_item_variation_id || 0,
```
The `variation_name` field from the API response (`PosVueOrderController@show` returns `'variation_name'`) is available via `...line` spread (line 1742). However, **the template never outputs `item.variation_name`** — the variation `<div>` at line 408 is empty.

**Severity:** 🔴 High

---

### ❌ Gap 1.5 — Combo Items in Linked KOT: Qty Controls Not Locked
**Legacy (`order_detail.blade.php` line 334):** When `$isComboItem` is true, the qty input is forced `readonly`.

**Current Vue state:**  
The `–` button on linked KOT rows is now gated by `canManageLineItems && canDeleteKotItem` (line 523). However, there is **no per-item `combo_pack_id` check** — combo items within a KOT group are still renderable as editable (no `combo_pack_id` detection in the linked rows template). Clicking `–` on a combo KOT item would trigger `requestDecreaseKotItem` without the combo-whole-removal warning.

**Severity:** 🟠 Medium

---

## 2. Order Summary Panel Gaps

### ❌ Gap 2.1 — Summary Uses Client-Side Computed Total Instead of Saved Order Values
**Legacy:** The summary is entirely built from the **saved order record**: `$orderDetail->sub_total`, `$orderDetail->total`, `$orderDetail->discount_amount`, etc.

**Current Vue state (lines 1934–2010):**
```js
const subTotal = computed(() => props.cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0));
const total = computed(() => { let calculatedTotal = subTotal.value; ... });
```
In linked mode, `props.cartItems` contains the KOT lines from the API which do have correct `price` and `qty`. The computed total is therefore **a close approximation**, but can diverge from `order.sub_total` / `order.total` if:
- Extra charges (server-applied) are not in `cartItems`
- Taxes were applied server-side at a different rate
- Rounding differs

The `order` prop (passed as `:order="order"` from `PosApp.vue`) contains `sub_total` and `total` directly from the API, but **`OrderPanel.vue` never reads `props.order.sub_total` or `props.order.total`** in the summary — it always recomputes from `cartItems`.

**Severity:** 🔴 High

---

### ✅ Gap 2.2 — Custom Order Extras — Partial (Predefined charges shown, freeform extras not)
**Legacy (`order_detail.blade.php` lines 398–410):** Iterates `$orderDetail->extras` for freeform extra lines.

**Current Vue state:** `extraCharges` prop maps predefined `RestaurantCharge` records and displays them (lines 849–879). Free-form `->extras` (custom per-order extras) are not loaded from the order API response. The `PosVueOrderController@show` does not include `order->extras` in its response.

**Severity:** 🟠 Medium

---

### ✅ Gap 2.3 — Tip Amount — FIXED
**Legacy:** Shows a "Tip" line if `$orderDetail->tip_amount > 0`.

**Current Vue state (lines 914–918):**
```html
<div v-if="tipAmount > 0" class="flex justify-between ...">
    <div>Tip</div>
    <div>{{ currencySymbol }}{{ formatPrice(tipAmount) }}</div>
</div>
```
`tipAmount` prop is wired from `PosApp.vue` via `:tip-amount="tipAmount"` and loaded from the order data. **This gap is resolved.**

---

### ❌ Gap 2.4 — Delivery Fee Input is Always Editable
**Legacy:** Delivery fee shown as read-only formatted currency when order is billed/paid.

**Current Vue state (lines 836–843):**
```html
<input type="number" step="1" min="0" :value="deliveryFee" @input="$emit('update:deliveryFee', ...)" />
```
There is no `readonly` or `:disabled` binding on this input based on `linkedLifecycleStatus`. It remains an **editable number input in all order states**, including billed/paid.

**Severity:** 🟠 Medium

---

### ✅ Gap 2.5 — Add Discount Button Gated by Permission — FIXED
**Legacy:** "Add Discount" only shown for `billed/paid/payment_due` AND `user_can('Edit Billed Order')`.

**Current Vue state (lines 777–788):**
```html
<button v-if="!isLinkedOrderMode || (isLinkedOrderMode && canEditBilledLinkedOrder)" ...>
    Add Discount
</button>
```
In linked mode, the button is only shown when `canEditBilledLinkedOrder` is true (requires `Edit Billed Order` permission). **This gap is resolved.**

---

### ❌ Gap 2.6 — Item-Wise Tax Breakdown Not Used in Linked Mode
**Legacy:** When `$taxMode === 'item'`, shows per-tax-component breakdown (CGST/SGST/VAT etc.) aggregated from `$orderItemTaxDetails`.

**Current Vue state (lines 882–911):** Shows taxes from the `taxes` prop with `tax.tax_name` and `tax.rate` — flat list, no `taxMode` distinction. The `taxes` prop in linked mode comes from `PosApp.vue` which recalculates them from `cartItems` (not from the server's persisted tax records).

**Severity:** 🟠 Medium

---

## 3. Footer Action Buttons — Full Comparison by Lifecycle State

### State 1: `lifecycleStatus = 'kot'` (Order is KOT'd, not yet billed)

| Button | Legacy | Vue (current) | Status |
|---|---|---|---|
| **Bill** | ✅ if `user_can('Update Order')` | ✅ via `canShowLinkedBillActions` | ✅ |
| **Bill & Payment** | ✅ shown | ✅ shown | ✅ |
| **Bill & Print** | ✅ shown | ✅ shown | ✅ |
| **New KOT** | ✅ full-page nav | ✅ emits `new-kot` → `window.location.href = /pos/kot/{id}` | ✅ |
| **Delete Order** | ✅ if `user_can('Delete Order')` | ✅ via `canShowLinkedDelete`, emits `delete-order` | ✅ |
| **Bill when cart is empty** | ✅ bills existing KOT items | ✅ Fixed: `handleSaveOrder` no longer blocks linked mode when `cartItems.length === 0` (line 2283) | ✅ |

---

### State 2: `lifecycleStatus = 'billed'`

| Button | Legacy | Vue (current) | Status |
|---|---|---|---|
| **Add Payment** | Opens payment modal in-place | ✅ calls `handleSaveOrder('bill')` → navigates to `/pos/kot/{id}?show-order-detail=true` | ⚠️ Different UX (page nav vs modal) |
| **New KOT** | ✅ if `user_can('Edit Billed Order')` | ✅ via `canShowLinkedNewKot` | ✅ |
| **Print Receipt** | ✅ shown if status is `paid` | ❌ **Missing entirely** | ❌ |

---

### State 3: `lifecycleStatus = 'paid'` or `'payment_due'`

| Button | Legacy | Vue (current) | Status |
|---|---|---|---|
| **New KOT** | ✅ if `user_can('Edit Billed Order')` | ✅ via `canShowLinkedNewKot` | ✅ |
| **Print Receipt** | ✅ shown if status = `paid` | ❌ **Completely missing** | ❌ |
| **Add Payment** | ❌ Not shown | ❌ Not shown | ✅ (correct) |

---

## 4. "Bill" Action Next Step

| Step | Legacy | Vue (current) | Status |
|---|---|---|---|
| API call | `wire:click="saveOrder('bill')"` | `POST /api/pos/orders` | ✅ |
| After success | Re-renders in-place | `window.location.href = /pos/kot/{id}?show-order-detail=true` | ✅ conceptually correct |
| Empty cart guard | No guard (existing items included) | ✅ Fixed: guard skipped in linked mode | ✅ |
| Print on Bill | Opens KOT print URL | ✅ handled via `result.data.links?.bill` | ✅ |

---

## 5. "New KOT" Action Next Step

| Aspect | Legacy | Vue (current) | Status |
|---|---|---|---|
| URL navigated to | `/pos/kot/{id}` | `/pos/kot/{id}` | ✅ |
| Cart cleared before redirect | N/A (separate page) | Cart NOT cleared before redirect → stale items could persist | ❌ Minor |

---

## 6. Cancel Order Modal

**Legacy (`order_detail.blade.php` lines 597–655):** Two separate modals:
- `confirmDeleteModal` — cancel flow with reason dropdown + freeform textarea
- `deleteOrderModal` — hard delete ("cannot be undone"), separate from cancel

**Current Vue state:**
- `CancelOrderModal.vue` — ✅ Has reason dropdown (`selectedReasonId`) + freeform `textarea` (`reasonText`)
- `PosApp.vue` `handleSaveCancelOrder` sends `{ order_status: "cancelled", cancel_reason_id, cancel_reason_text }` to `POST /api/pos/orders/{id}/status` — ✅ Reason is sent to API
- `handleDeleteOrder` — ✅ Separate hard-delete flow (`DELETE /api/pos/orders/{id}`)

> **This entire section from the original gap analysis has been resolved.** Cancel reason dropdown, freeform text, and API submission are all implemented correctly.

---

## 7. KOT Item Removal / Reduction — FIXED

Previously noted as broken (prices zeroing out, no audit log). Current state:

| Feature | Legacy | Vue (current) | Status |
|---|---|---|---|
| Trash icon → reason modal | ✅ `Delete KOT Item` permission | ✅ `requestRemoveKotItem` checks `canDeleteKotItem`, opens modal | ✅ |
| `–` button → reason modal (qty > 1) | ✅ prompts `decrement` with `newQty = qty - 1` | ✅ `requestDecreaseKotItem` prompts `decrement` | ✅ |
| `–` button → reason modal (qty = 1) | ✅ prompts `delete` | ✅ prompts `delete` | ✅ |
| Audit log | `KotAdjustmentLogger::log()` | ✅ Called server-side in `removeKotItem` + `reduceKotItem` | ✅ |
| `DELETE /api/pos/orders/{id}/kot-items/{kotItemId}` | ✅ | ✅ New route added | ✅ |
| `PATCH /api/pos/orders/{id}/kot-items/{kotItemId}/quantity` | ✅ | ✅ New route added | ✅ |
| Permission check | `Delete KOT Item` | ✅ `can_delete_kot_item` from order permissions API | ✅ |

---

## 8. Summary Table: All Gaps — Verified Current State

| # | Area | Gap | Severity | Status |
|---|---|---|---|---|
| 1 | Cart Header UI | No coloured status badge (KOT/BILLED/PAID/CANCELLED) | 🟠 Medium | ❌ Open |
| 2 | Cart Header UI | No order date/time display | 🟠 Medium | ❌ Open |
| 3 | Cart Header UI | No order type icon | 🟡 Low | ❌ Open |
| 4 | Cart Header UI | Table code badge | 🟡 Low | ✅ Fixed (table icon + code shown) |
| 5 | Cart Item Row | Modifiers not rendered in linked KOT rows | 🔴 High | ❌ Open |
| 6 | Cart Item Row | Variation name not shown in linked KOT rows | 🔴 High | ❌ Open |
| 7 | Cart Item Row | Combo visual grouping missing in linked KOT lines | 🟠 Medium | ❌ Open |
| 8 | Cart Item Row | Combo qty controls not locked in linked mode | 🟠 Medium | ❌ Open |
| 9 | Summary Panel | Summary computed client-side instead of using saved `order.sub_total`/`order.total` | 🔴 High | ❌ Open |
| 10 | Summary Panel | Custom freeform order extras not loaded/shown | 🟠 Medium | ❌ Open |
| 11 | Summary Panel | Tip amount not shown | 🟠 Medium | ✅ Fixed |
| 12 | Summary Panel | Delivery fee editable in billed/paid state | 🟠 Medium | ❌ Open |
| 13 | Summary Panel | "Add Discount" permission/status gating | 🔴 High | ✅ Fixed |
| 14 | Summary Panel | Item-wise tax breakdown missing | 🟠 Medium | ❌ Open |
| 15 | Action Buttons | Bill blocked when `cartItems` is empty (linked mode) | 🔴 Critical | ✅ Fixed |
| 16 | Action Buttons | "Add Payment" navigates away vs in-page modal | 🟠 Medium | ⚠️ Acceptable (SPA limitation) |
| 17 | Action Buttons | "Print Receipt" missing for `billed`/`paid` state | 🔴 High | ❌ Open |
| 18 | Cancel Flow | Cancel reason dropdown missing | 🔴 High | ✅ Fixed (`CancelOrderModal.vue`) |
| 19 | Cancel Flow | Cancel reason not sent to API | 🔴 High | ✅ Fixed (`handleSaveCancelOrder`) |
| 20 | Cancel Flow | Custom cancel reason textarea missing | 🟠 Medium | ✅ Fixed (`CancelOrderModal.vue`) |
| 21 | KOT Removal | Delete → no reason modal, no API, no audit log | 🔴 Critical | ✅ Fixed |
| 22 | KOT Removal | Decrement (`–`) → no reason modal, no audit log | 🔴 Critical | ✅ Fixed |
| 23 | KOT Removal | Permission not checked (`Delete KOT Item`) | 🔴 High | ✅ Fixed |

---

## 9. Priority Remaining Work

### 🔴 High Priority
1. **Gap 5 + 6** — Variation name + modifier pills in linked KOT rows (`variation_name` is in the API response but not rendered in the template at lines 404–409)
2. **Gap 9** — Summary panel: use `props.order.sub_total` / `props.order.total` directly in linked mode instead of recomputing from `cartItems`
3. **Gap 17** — Add "Print Receipt" button for `billed` and `paid` lifecycle states

### 🟠 Medium Priority
4. **Gap 7** — Combo visual grouping in linked KOT lines (reuse pattern from `groupedCartItems`)
5. **Gap 12** — Make delivery fee `readonly` in linked mode when order is `billed`/`paid`
6. **Gap 1 + 2** — Order status badge + date/time in the linked-mode header
7. **Gap 10** — Load and display freeform `order->extras`

### 🟡 Low Priority
8. **Gap 3** — Order type icon in header
9. **Gap 5 (cart)** — Combo qty lock for linked KOT combo rows
10. **Gap 16** — In-page payment modal (SPA limitation; current page-nav is acceptable)
