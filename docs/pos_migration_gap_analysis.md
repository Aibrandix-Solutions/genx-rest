# POS Migration Gap Analysis: Livewire → Vue

> **Analysis Date:** April 18, 2026  
> **Legacy System:** Livewire server-bound POS (`resources/views/pos/`, `resources/views/livewire/pos/`)  
> **New System:** Vue SPA POS (`resources/js/PosApp.vue`, `resources/js/components/pos/`)

---

## Overview: How Far We've Come

The Vue POS is functionally **~65–70% complete**. The core skeleton — menu browsing, cart, order type switching, save actions (KOT/Bill/Payment), table assignment, customer management, offline mode, and the linked-order (edit) flow — is all implemented. The remaining gaps are mostly around **financial detail accuracy** (item-wise tax, extra charges on existing orders, custom order extras), **Combo Pack display in the new cart**, **Pickup date/time**, and **several UI fidelity differences**.

---

## ✅ What's Fully Migrated (feature parity confirmed)

| Feature | Legacy | Vue |
|---|---|---|
| Menu panel with search, category filter, menu tabs | ✅ | ✅ |
| Add item to cart (simple items) | ✅ | ✅ |
| Item variations modal (with order-type pricing) | ✅ | ✅ |
| Item modifiers modal | ✅ | ✅ |
| Qty increment / decrement / direct input | ✅ | ✅ |
| Remove cart item | ✅ | ✅ |
| Order type selector (Dine In / Pickup / Delivery) with "Set as Default" | ✅ | ✅ |
| Delivery platform selector | ✅ | ✅ |
| Contextual menu-item pricing per order-type/delivery app | ✅ | ✅ |
| Table assignment modal (Set Table / Change Table) | ✅ | ✅ |
| Table change confirmation modal | ✅ | ✅ |
| Pax (number of guests) | ✅ | ✅ |
| Waiter selector (with role restriction) | ✅ | ✅ |
| Customer add/edit/remove | ✅ | ✅ |
| Order Note (whole-order) | ✅ | ✅ |
| Per-item note (Special Instructions inline) | ✅ | ✅ |
| Discount (fixed & percent) | ✅ | ✅ |
| Remove discount | ✅ | ✅ |
| Sub-total, discount, total display | ✅ | ✅ |
| Taxes display (order-level) | ✅ | ✅ (basic) |
| Delivery fee editable | ✅ | ✅ |
| Delivery executive selector | ✅ | ✅ |
| Save order: KOT, KOT & Print, KOT+Bill+Payment | ✅ | ✅ |
| Save order: Bill, Bill & Payment, Bill & Print | ✅ | ✅ |
| Linked-order mode (editing existing order): New KOT, Bill, Delete Order | ✅ | ✅ |
| Order status flow panel (placed→confirmed→preparing→served/delivered) | ✅ | ✅ |
| Cancel Order (with reason selection) | ✅ | ✅ |
| Reservation confirmation modal (same/different customer) | ✅ | ✅ |
| KOT Groups display in linked-order mode | ✅ | ✅ |
| Offline mode (queue ops, sync when back online) | ❌ | ✅ **NEW** |
| Beep sound on item add | ✅ | ✅ |
| Order number display (formatted) | ✅ | ✅ |
| Offline order number increment | ❌ | ✅ **NEW** |
| Delivery address + map link (linked orders) | ❌ | ✅ **NEW** |
| Customer phone link | ❌ | ✅ **NEW** |

---

## ❌ Functionality Gaps (Missing or Broken in Vue)

### 1. CRITICAL — Combo Pack Support in Cart (New Orders)
**Legacy:** Both `kot_items.blade.php` and `order_items.blade.php` fully render combo packs with:
- Blue header row showing combo pack name
- "Save XYZ" savings badge
- COMBO badge per line item
- Strikethrough original price / discounted price
- "Remove whole combo" button on the header row
- Combo savings total in the order summary

**Vue (`OrderPanel.vue`):** The `cartItems` array shape only stores `combo_pack_id` and `combo_instance_key`, but:
- ⚠️ `OrderPanel.vue` **does not render** combo group headers or savings in the new-order (non-linked) cart view
- ⚠️ `handleAddToCart` in `PosApp.vue` doesn't have a `queueAddCombo` pathway (combo packs come via the MenuPanel but are not added to cart as grouped combos)
- ⚠️ "Combo Savings" subtotal line **is missing** in `OrderPanel.vue` summary (Legacy shows `$totalComboSavings` deduction)
- ✅ Linked-order mode *does* show KOT groups (read-only), which includes combo items indirectly

---

### 2. CRITICAL — Item-Wise Tax Mode (`tax_mode = 'item'`)
**Legacy (`kot_items.blade.php` lines 584–641):** When `$taxMode === 'item'`, it iterates `$orderItemTaxDetails` and renders each tax component (e.g., CGST 9%, SGST 9%) per line item, aggregated and shown individually above "Total Tax".

**Vue (`OrderPanel.vue`):** Only a simple flat tax list from `availableTaxes` is computed and displayed. There is:
- ⚠️ No `taxMode` concept (order-level vs item-level)
- ⚠️ No per-item tax breakdown aggregation
- ⚠️ Tax-inclusive vs tax-exclusive label is present in Vue but tax calculation is always applied to the full post-discount subtotal (order-level only)

---

### 3. HIGH — Custom Order Extras (`allow_custom_order_extras`)
**Legacy (`order_items.blade.php` lines 695–728):** When `restaurant()->allow_custom_order_extras` is enabled, the billed-order view shows:
- An "Add" button to append freeform `amount + note` extra lines
- Each line displayed in the summary
- Total extras factored into charges

**Vue:** `extraCharges` prop exists but only handles predefined `ExtraCharge` records (database-driven). There is:
- ⚠️ No custom freeform extra rows UI
- ⚠️ No `addOrderExtraRow` / `removeOrderExtraRow` equivalent

---

### 4. HIGH — Pickup Date/Time Field
**Legacy (`order_items.blade.php` lines 115–130 and `kot_items.blade.php` lines 218–232):** When order type is `pickup`, shows a `datetime-local` input with min/max date constraints from server (`$minDate`, `$maxDate`, `$defaultDate`).

**Vue (`OrderPanel.vue`):** 
- ⚠️ **Completely missing** — there is no pickup date/time input anywhere in the Vue POS

---

### 5. HIGH — Item Removal Reason Modal (Staff Adjustment Note)
**Legacy (`pos.blade.php` lines 38–57):** A `showRemovalReasonModal` dialog forces staff to enter a reason before removing a line item from an existing billed/paid order (protected by permissions).

**Vue:**
- ⚠️ **Not implemented** — items can be removed without any reason/audit trail in Vue

---

### 6. HIGH — KOT Module Gate
**Legacy (`kot_items.blade.php` line 654):** KOT buttons are only shown `if (in_array('KOT', restaurant_modules()))` — i.e., gated by the KOT module subscription.

**Vue (`OrderPanel.vue`):**
- ⚠️ KOT/KOT &amp; Print buttons are **always shown** regardless of whether the KOT module is enabled

---

### 7. MEDIUM — Extra Charges on Existing (Linked) Orders
**Legacy (`order_items.blade.php` lines 753–775):** The billed/existing order view shows each `ExtraCharge` with a remove button (`removeExtraCharge`), calculating their amount based on `subTotal + extras - discount`.

**Vue (`OrderPanel.vue` lines 893–924):** It renders `extraCharges` from props, but:
- The remove button emits `remove-extra-charge` — but `PosApp.vue` has **no handler** for `@update:extraCharges` or `remove-extra-charge` that calls an API endpoint
- Extra charges are loaded in `loadOrderData` from the order payload — but the API response needs to include them (verify API contract)

---

### 8. MEDIUM — Tip Amount Display
**Legacy (`order_items.blade.php` lines 777–786):** When `$tipAmount > 0`, shows a "Tip" line in the order summary.

**Vue:**
- ⚠️ `tipAmount` is never loaded, stored, or displayed anywhere in the Vue POS

---

### 9. MEDIUM — Order Item Adjustment Note on Removal (Modifiers context)
**Legacy:** `showRemovalReasonModal` exists specifically for compliance when removing items from billed orders.  
**Vue:** No equivalent modal or reason capture.

---

### 10. LOW — Pax Not Shown for Non-Dine-In Orders
**Legacy (`kot_items.blade.php` line 141):** Pax is shown only `if ($orderType == 'dine_in')`.

**Vue (`OrderPanel.vue` lines 229–235):** Pax input is **always visible** regardless of order type — minor UX regression.

---

### 11. LOW — Order Note Dot Indicator
**Legacy (`kot_items.blade.php` lines 148–163):** The note button shows a small colored dot when a note exists (`if ($this->orderNote)`).

**Vue (`OrderPanel.vue` lines 237–249):** The note button has **no indicator** when a note is set.

---

### 12. LOW — KOT Print uses legacy URL scheme; "ItemVariations" in Linked Mode
In linked mode, the legacy KOT print goes to `/pos/print/kot/{id}`. Vue uses `result.data.links?.kot` from API — this should be verified to return the correct URL.

---

## 🎨 UI / Design Gaps

| Element | Legacy | Vue Status |
|---|---|---|
| Order type panel layout | 3-column grid (select + checkbox + platform) | 1-column stacked — ✅ acceptable |
| Item name column min-width | `lg:min-w-28` | `lg:min-w-20` — slightly narrower |
| Price column visibility | Hidden on mobile (`hidden lg:table-cell`) | ✅ Same |
| Combo header row (blue accent) | ✅ Rendered | ❌ Missing in new-order cart |
| COMBO badge on item name | ✅ | ❌ Missing in new-order cart |
| Modifier display (pill rows) | ✅ `bg-gray-200 border-l-2 border-blue-500` | ❌ Modifiers not displayed in cart items |
| Order note indicator dot | ✅ SVG circle | ❌ Missing |
| Reservation details on modal | Shows customer name + reservation time | ✅ Same |
| Cart empty state | Language-translated message | English hardcoded "No record found" |
| Action button labels | Translated via `@lang()` | Hardcoded English strings |
| KOT button condition | `in_array('KOT', restaurant_modules())` | Always shown |
| Tooltips | Flowbite tooltip on note button | None |
| Delivery address card | ❌ Legacy had none | ✅ Vue added — improvement |
| Note indicator on order button | ✅ Dot indicator | ❌ Missing in Vue |

---

## 🔌 Missing API Contract Items to Verify

These are things the Vue POS calls/expects from server APIs that may not be in place:

| API | Status |
|---|---|
| `GET /api/pos/orders/{id}` — includes `lines`, `kots`, `permissions`, `delivery_address`, `customer_lat/lng` | Needs verification |
| `GET /api/pos/orders/{id}` — includes `extra_charges`, `tip_amount`, `delivery_fee` | Needs verification |
| `POST /api/pos/orders` — handles `combo_pack_id`, `combo_instance_key` on lines | Likely in place (server-side confirmed legacy) |
| `POST /api/pos/orders` — handles `custom_order_extras` rows | ❌ Not in Vue payload |
| `GET /api/pos/bootstrap` returns `allow_custom_order_extras` flag | Needs verification |
| `GET /api/pos/bootstrap` returns `tax_mode` per restaurant | Present (`bootstrap.tax_mode`) |
| Print KOT URL in response `data.links.kot` | Needs verification |

---

## 📋 Priority Remediation Plan

| Priority | Gap | Effort |
|---|---|---|
| 🔴 P1 | Pickup date/time field | Small |
| 🔴 P1 | Item removal reason modal | Medium |
| 🔴 P1 | Combo pack rendering in new-order cart | Large |
| 🔴 P1 | KOT module gate (only show KOT buttons if module enabled) | Small |
| 🟠 P2 | Item-wise tax mode support | Medium |
| 🟠 P2 | Custom order extras (freeform rows) | Medium |
| 🟠 P2 | Modifier display in cart | Small |
| 🟠 P2 | Tip amount display | Small |
| 🟡 P3 | Note indicator dot on note button | Tiny |
| 🟡 P3 | Pax visibility gated to Dine In only | Tiny |
| 🟡 P3 | i18n / translation strings (hardcoded English) | Medium |
| 🟡 P3 | Extra charges remove handler (API call) in PosApp.vue | Small |
| 🟢 P4 | Cart empty state translation | Tiny |
| 🟢 P4 | Combo savings total line in summary | Small |
