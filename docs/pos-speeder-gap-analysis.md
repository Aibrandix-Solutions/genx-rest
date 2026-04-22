# POS Speeder Gap Analysis

Date: 2026-04-18
Scope: Compare the current repo POS against the attached Speeder Vue POS implementation.

## 1. What Speeder Is Doing

Speeder is a Vue-first POS runtime:
- Cart state lives in Vue refs.
- Menu and reference data are loaded through a cache-first bootstrap flow.
- Offline mode is built into the same client runtime through queued operations and replay.
- The UI is owned by Vue components, not by Livewire/Blade page fragments.

That means screen flow, footer actions, and next-screen navigation are controlled in one place.

## 2. Major Technical Gap

The current repo is a hybrid POS, while Speeder is a single Vue runtime.

Current repo:
- Main POS still uses Blade/Livewire as the primary runtime in many routes.
- Vue linked-order support is layered on top with extra API hydration.
- Footer actions sometimes depend on Livewire dispatch fallbacks.

Speeder:
- Uses one Vue page/component tree for POS behavior.
- Keeps state, action handling, and navigation inside the Vue app.
- Uses API data and local state as the primary source of truth.

Implication:
- Our current repo can match behavior, but the implementation is more fragmented and easier to desync.

## 3. Screen-Flow Gap

Legacy Speeder-like flow is status-driven and route-driven:
- KOT view stays in KOT flow.
- Billed view switches to billed footer actions.
- Payment opens as an explicit next screen/action.
- Detail screens remain tied to the existing order context.

Current repo gaps:
- Linked mode detection was originally tied too closely to lifecycle status instead of route/context.
- Some footer actions were doing the right save call but not the right next screen.
- Linked detail and billed detail need separate footer states, not one merged state.

## 4. Footer Button Gaps

### 4.1 KOT state footer

Speeder / legacy expectation:
- Bill
- Bill & Payment
- Bill & Print
- New KOT
- Delete Order

Current repo gap:
- The same footer set was not consistently shown after a bill transition.
- Some actions saved correctly but did not reopen the expected screen.

### 4.2 Billed state footer

Speeder / legacy expectation:
- Add Payment
- New KOT

Current repo gap:
- Billed state was not rendered as a separate footer mode at first.
- Add Payment handling was not aligned to the legacy post-bill behavior.
- New KOT was treated like an immediate save instead of a navigation into KOT flow.

### 4.3 Delete Order

Speeder / legacy expectation:
- Confirm and delete the whole order.
- Return to POS.

Current repo:
- This is the closest match and was already behaving correctly.

## 5. Button-by-Button Behavior Gap

### Bill

Legacy behavior:
- Save as billed.
- Reopen the order detail drawer/view.

Current repo gap:
- Save happened, but reopening the detail drawer was not reliable until linked flow navigation was patched.

### Bill & Payment

Legacy behavior:
- Save as billed.
- Open payment flow immediately.

Current repo gap:
- Save happened, but payment screen opening was not consistently triggered.
- The fallback path was too dependent on Livewire dispatch success.

### Bill & Print

Legacy behavior:
- Save as billed.
- Open the print document/receipt flow.

Current repo gap:
- Save happened, but print was not being launched reliably.
- Print behavior needed an explicit URL / print-window fallback.

### New KOT

Legacy behavior:
- Navigate into the KOT creation flow for the same order.
- Do not immediately create a new order save from the footer click itself.

Current repo gap:
- This was the biggest semantic mismatch.
- The Vue button was wired to save a new KOT immediately.

### Delete Order

Legacy behavior:
- Delete the order and related KOT rows.
- Return to POS.

Current repo:
- This is already the closest match to legacy.

## 6. Data/Contract Gaps

Speeder-style Vue POS expects predictable payloads:
- Order number and formatted order number.
- KOT groups and line-level pricing.
- Permissions for update, delete, and billed edits.
- Delivery customer/location data for linked delivery cards.

Current repo gaps that were found during implementation:
- Linked payload initially did not always provide consistent order number fields.
- Linked footer decisions needed explicit permissions and status data.

Resolved:
- Variation line pricing was fixed by adding stronger unit-price resolution and amount fallback.

## 7. Next-Screen Gaps

The biggest next-screen differences are:
- After Bill, legacy goes back to order detail; Vue needed deterministic linked-detail navigation.
- After Bill & Payment, legacy opens payment flow; Vue needed a guaranteed redirect/open path.
- After Bill & Print, legacy opens a print route/window; Vue needed a stable print fallback.
- New KOT should move to the KOT route, not save immediately.

## 8. Summary

The biggest remaining gap is not variation pricing. It is the combination of:
- runtime ownership,
- linked state detection,
- footer mode switching by order status,
- and post-action next-screen behavior.

In practical terms, the main remaining work is keeping Vue behavior aligned to legacy state transitions while preserving the Speeder-style single-page runtime model.

## 9. Action Button Fix Checklist

Objective: match legacy behavior for footer mode switching and post-action next screens in linked order detail flow.

### 9.1 Footer Mode Switching

- [x] Ensure linked footer mode is derived from order detail context, not only lifecycle status.
- [x] Ensure KOT status renders the KOT footer set only:
	- Bill
	- Bill & Payment
	- Bill & Print
	- New KOT
	- Delete Order (permission-gated)
- [x] Ensure billed status renders the billed footer set only:
	- Add Payment
	- New KOT (permission-gated)
- [x] Ensure paid/payment_due status hides KOT billing actions and keeps only allowed New KOT behavior.

### 9.2 Button Semantics

- [x] Bill: save billed and open the side drawer of order detail in same screen, Keep billed-state button visual order and layout consistent with legacy screenshot:
	- Add Payment row
	- New KOT row (outlined style)
- [x] Bill & Payment: save billed and open payment flow as the next screen.
- [x] Bill & Print: save billed and open print flow reliably.
- [x] New KOT: navigate to KOT route for the same order (do not immediately save on click).
- [x] Delete Order: confirm, delete order and related KOT rows, then return to POS.

### 9.3 Permission Gates

- [x] Apply `can_update_order` gate for Bill/Bill & Payment/Bill & Print.
- [x] Apply `can_delete_order` gate for Delete Order.
- [x] Apply `can_edit_billed_order` gate for billed/paid/payment_due New KOT behavior.

### 9.4 Next-Screen Reliability

- [x] Remove dependency on optional Livewire event success for critical navigation.
- [x] Keep deterministic URL fallback paths for detail/payment/print screens.
- [x] Verify no action ends on a stale panel after success.

### 9.5 UI Parity

- [x] Keep billed-state button visual order and layout consistent with legacy screenshot:
	- Add Payment row
	- New KOT row (outlined style)
- [x] Keep KOT-state button labels and order consistent with legacy.

### 9.6 Regression Checks

- [ ] Non-linked fresh POS flow still works (KOT/BILL/BILL+PAYMENT/BILL+PRINT).
- [ ] Linked delivery card and customer controls remain permission-safe.
- [x] Variation pricing remains non-zero in linked view after action transitions.

### 9.7 Validation Run

- [x] Build passes (`npm run build`).
- [ ] Route smoke checks:
	- `/pos/kot/{id}?show-order-detail=true`
	- `/pos/kot/{id}`
	- `/orders/{id}?payment=true`
	- `/orders/print/{id}`
- [ ] Manual QA on one order per status:
	- kot
	- billed
	- paid/payment_due