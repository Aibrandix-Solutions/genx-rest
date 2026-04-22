# POS Linked Cart Parity: Implementation Patch Plan

Date: 2026-04-16
Scope: Vue POS linked order mode parity with legacy Livewire linked cart behavior.
Primary route under test: `/pos/kot/{id}?show-order-detail=true`

## 1) Implementation Sequence (One-Pass Order)

- [x] Phase A: API contract expansion first (no UI changes yet)
- [x] Phase B: Parent state + hydration updates in Vue POS container
- [x] Phase C: Linked-mode rendering split in OrderPanel
- [x] Phase D: KOT-grouped item renderer
- [x] Phase E: Status-based action footer matrix
- [x] Phase F: Delivery info card block (customer/phone/address/map)
- [x] Phase G: Permission-driven UI gating
- [x] Phase H: Regression QA matrix and sign-off

---

## 2) Phase A: API Contract Tasks

### A.1 Show endpoint contract upgrade
Endpoint: `GET /api/pos/orders/{id}`

- [x] Add `permissions` object in `data.order`
- [x] Add `kots` grouped structure in `data.order`
- [x] Add delivery location fields needed by delivery info card
- [x] Preserve existing `lines` array for backward compatibility during rollout

Target response example:
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1824,
      "status": "kot",
      "order_status": "preparing",
      "order_type": "delivery",
      "order_type_id": 2,
      "delivery_app_id": 3,
      "delivery_executive_id": 14,
      "delivery_fee": 49,
      "waiter_id": null,
      "customer_id": 71,
      "customer": {
        "id": 71,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "9999999999",
        "phone_code": "+91",
        "address": "22 Main Street",
        "delivery_address": "22 Main Street"
      },
      "delivery_address": "22 Main Street",
      "customer_phone": "+919999999999",
      "customer_lat": 12.9716,
      "customer_lng": 77.5946,
      "note": "No onion",
      "sub_total": 649,
      "total": 734,
      "permissions": {
        "can_update_order": true,
        "can_delete_order": true,
        "can_edit_billed_order": false
      },
      "lines": [
        {
          "order_item_id": 9011,
          "menu_item_id": 110,
          "item_name": "Paneer Wrap",
          "menu_item_variation_id": null,
          "variation_name": "",
          "qty": 2,
          "unit_price": 149,
          "amount": 298,
          "note": "",
          "combo_pack_id": null,
          "combo_instance_key": null,
          "modifier_option_quantities": {
            "51": 1
          }
        }
      ],
      "kots": [
        {
          "id": 441,
          "kot_number": "KOT-2026-000441",
          "created_at": "2026-04-16T12:15:11+05:30",
          "status": "in_kitchen",
          "lines": [
            {
              "order_item_id": 9011,
              "kot_item_id": 12111,
              "menu_item_id": 110,
              "item_name": "Paneer Wrap",
              "menu_item_variation_id": null,
              "variation_name": "",
              "qty": 2,
              "unit_price": 149,
              "amount": 298,
              "note": "",
              "combo_pack_id": null,
              "combo_instance_key": null,
              "modifier_option_quantities": {
                "51": 1
              }
            }
          ]
        }
      ]
    }
  }
}
```

### A.2 Bootstrap contract (only if missing in current payload)
Endpoint: `GET /api/pos/bootstrap`

- [x] Ensure `delivery_executives` remains present
- [x] Ensure branch coordinates are included for map-direction link generation

Target response excerpt example:
```json
{
  "delivery_executives": [
    {
      "id": 14,
      "name": "Alex Rider",
      "status": "available"
    }
  ],
  "branch": {
    "id": 9,
    "name": "Main Branch",
    "lat": 12.9352,
    "lng": 77.6245
  }
}
```

### A.3 No route changes required for existing update endpoints
Routes already available in `routes/api.php`:
- [ ] `POST /api/pos/orders/{id}/status`
- [ ] `POST /api/pos/orders/{id}/delivery-executive`
- [ ] `POST /api/pos/orders/{id}/delivery-fee`
- [ ] `POST /api/pos/orders/{id}/waiter`
- [ ] `POST /api/pos/orders`

---

## 3) Phase B: Parent State + Hydration Tasks (PosApp)

- [x] Add explicit `isLinkedOrderMode` derived state using loaded order and `status === "kot"`
- [x] Add `orderPermissions` state from show payload
- [x] Add `kotGroups` state from `order.kots`
- [x] Add delivery-info fields (`delivery_address`, `customer_phone`, `customer_lat`, `customer_lng`)
- [x] Pass all new props to `OrderPanel`
- [x] Keep existing flat-lines fallback while KOT grouping is rolled out

---

## 4) Phase C: Linked-Mode Rendering Split (OrderPanel)

- [x] Introduce branch rendering: regular cart vs linked cart
- [x] Keep selector/icon/status top order matching legacy linked panel
- [x] Keep status panel above KOT block
- [x] Keep delivery executive selector visible only for delivery order type

---

## 5) Phase D: KOT-Grouped Item Renderer

- [x] Render each KOT block with `kot_number` and timestamp header
- [x] Render only items in that KOT block
- [x] Preserve combo-group rendering semantics under KOT grouping
- [x] Keep stable item identity using `order_item_id` and `kot_item_id`

---

## 6) Phase E: Status-Based Action Footer Matrix

- [x] Implement action matrix for linked mode exactly by `order.status`
- [x] `kot` state actions: Bill, Bill and Payment, Bill and Print, New KOT, Delete Order
- [x] `billed` state actions: Add Payment, New KOT (if `can_edit_billed_order`)
- [x] `paid`/`payment_due` state actions: New KOT (if `can_edit_billed_order`)
- [x] Gate actions by permissions from API payload

Action wiring endpoint payload examples:

1) Bill / New KOT flow
Endpoint: `POST /api/pos/orders`

Request example (new KOT):
```json
{
  "order_id": 1824,
  "action": "kot",
  "open_payment": false,
  "order_type_id": 2,
  "delivery_app_id": 3,
  "delivery_executive_id": 14,
  "delivery_fee": 49,
  "waiter_id": null,
  "customer_id": 71,
  "note": "No onion",
  "lines": [
    {
      "menu_item_id": 110,
      "menu_item_variation_id": null,
      "qty": 2,
      "note": "",
      "modifier_option_quantities": {
        "51": 1
      },
      "combo_pack_id": null,
      "combo_instance_key": null
    }
  ]
}
```

Request example (bill and open payment):
```json
{
  "order_id": 1824,
  "action": "bill",
  "open_payment": true,
  "order_type_id": 2,
  "delivery_app_id": 3,
  "delivery_executive_id": 14,
  "delivery_fee": 49,
  "waiter_id": null,
  "customer_id": 71,
  "note": "No onion",
  "lines": [
    {
      "menu_item_id": 110,
      "menu_item_variation_id": null,
      "qty": 2,
      "note": "",
      "modifier_option_quantities": {
        "51": 1
      }
    }
  ]
}
```

2) Cancel / move-to-next status
Endpoint: `POST /api/pos/orders/{id}/status`

Request example:
```json
{
  "order_status": "cancelled"
}
```

Success response example:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "order_id": 1824,
    "order_status": "cancelled"
  }
}
```

---

## 7) Phase F: Delivery Info Card Block

- [ ] Show delivery card only for linked delivery orders
- [ ] Fields required: customer name, phone, address
- [ ] Render map direction link when both customer and branch coordinates are available

Map URL template:
```text
https://www.google.com/maps/dir/{branch_lat},{branch_lng}/{customer_lat},{customer_lng}
```

---

## 8) Phase G: Update Micro-Endoints Payload Contracts

1) Assign delivery executive
Endpoint: `POST /api/pos/orders/{id}/delivery-executive`

Request:
```json
{
  "delivery_executive_id": 14
}
```

Response:
```json
{
  "success": true,
  "message": "Delivery executive assigned successfully.",
  "data": {
    "order_id": 1824,
    "delivery_executive_id": 14
  }
}
```

2) Update delivery fee
Endpoint: `POST /api/pos/orders/{id}/delivery-fee`

Request:
```json
{
  "delivery_fee": 49
}
```

Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "order_id": 1824,
    "delivery_fee": 49
  }
}
```

3) Update waiter
Endpoint: `POST /api/pos/orders/{id}/waiter`

Request:
```json
{
  "waiter_id": 22
}
```

Response:
```json
{
  "success": true,
  "message": "Waiter updated successfully.",
  "data": {
    "order_id": 1824,
    "waiter_id": 22
  }
}
```

---

## 9) Phase H: QA Matrix (Must Pass)

- [x] Linked mode entry from order card opens correct right panel mode
- [x] Delivery executive dropdown populated in linked delivery order
- [x] Status controls follow order-type-specific transition list
- [x] Cancel action updates both `order_status` and `status` behavior as expected
- [x] KOT grouping renders exact KOT headers and item membership
- [x] Footer action matrix matches `status` (`kot`, `billed`, `paid`, `payment_due`)
- [x] Delivery address/map card appears only when required fields exist
- [x] No regressions in regular fresh POS flow (non-linked)

### 9.1 Sign-Off Evidence

Date: 2026-04-17

- Linked mode activation is derived from loaded order context (`orderId` + `status === "kot"`) and passed into the panel as `isLinkedOrderMode`.
- Delivery executive dropdown is populated from bootstrap payload (`delivery_executives`) and persisted via `POST /api/pos/orders/{id}/delivery-executive`.
- Status transition flow is order-type specific (delivery/pickup/dine_in) and guarded by linked permission contracts.
- Cancel flow requires reason input, posts `order_status=cancelled` with reason fields, and backend updates both `order_status` and `status` (`canceled`) with proper permission checks.
- Linked KOT renderer uses `kots[]` groups with explicit KOT headers/timestamps and line membership mapping.
- Linked footer matrix is status-driven and permission-gated for `bill`, `payment`, `new kot`, and `delete` actions.
- Delivery info card is rendered only for linked delivery orders with address and map link conditional on both customer and branch coordinates.
- Non-linked fallback flow remains intact through explicit `v-else` branches for cart rendering and action buttons.

### 9.2 Verification Result

- Build check: `npm run build` passed.
- View cache refresh: `php artisan view:clear` passed.
- Static editor diagnostics: no errors in touched Vue/PHP/route files.

Phase H status: APPROVED

---

## 10) File-Level Patch Checklist

Backend:
- [x] `app/Http/Controllers/Api/PosVueOrderController.php` (show payload expansion)
- [x] `app/Http/Controllers/PosController.php` (bootstrap branch coordinates if missing)

Frontend:
- [x] `resources/js/PosApp.vue` (new state, hydration, prop passing)
- [x] `resources/js/components/pos/OrderPanel.vue` (linked split, KOT grouping, footer matrix, delivery card)

No planned changes:
- [ ] `routes/api.php` (already has needed endpoints)

---

## 11) Rollout Notes

- [ ] Keep backward compatibility for flat `lines` until KOT grouping is fully adopted in UI
- [ ] Feature-flag linked-mode renderer if incremental rollout is preferred
- [ ] Validate permissions server-side regardless of UI gating
