# POS Architecture Gap Analysis

This document compares the Speeder POS architecture observed in `C:\xampp\htdocs\table` with the current `genx-rest` POS implementation.

The goal is to identify the exact methodology Speeder uses for POS and related features, what `genx-rest` already has, and what still has to change to reach the same model.

## Executive Summary

Speeder is a Vue-first, API-first, client-state-owned POS. The cart and most UI behavior live in the browser, not in Livewire. The server provides bootstrap data, order/customer/table/order-type APIs, and order save/load endpoints. Offline mode is built into the client through queueing, local cache, and retry sync.

`genx-rest` now has many of the same backend surfaces and a matching Vue app, and the default POS entry routes now run through the Vue POS screen. The remaining gap is finishing full behavioral parity and safely deprecating legacy Blade/Livewire-only branches.

## Speeder Methodology

### 1. Client-owned state

Speeder's POS is centered on a Vue app mounted by `resources/js/pos-app.js` into `#pos-app`.

Core state lives in Vue `ref`s and `computed` values inside `resources/js/PosApp.vue`:

-   `cartItems`
-   `menus`, `categories`, `menuItems`, `waiters`, `comboPacks`
-   `taxes`, `extraCharges`, `discountAmount`, `discountType`, `discountValue`
-   `orderType`, `orderNumber`, `currentTable`, `customer`
-   `isOnline`, `pendingOperations`

This means add, quantity change, remove, customer update, and order save all operate on client state first.

### 2. Bootstrap-first loading

Speeder uses a cached bootstrap service as the initial source of read-only POS data.

Observed flow:

-   `PosBootstrapService` provides restaurant/branch-scoped bootstrap data.
-   `PosBootstrapController` serves `/api/pos/bootstrap`.
-   The Vue app reads an initial `data-bootstrap` payload from the mount point and then refreshes from API/cache.

The bootstrap payload includes the stable data needed to render the POS without multiple first-load round trips:

-   categories
-   order types
-   delivery platforms
-   waiters
-   taxes
-   tax mode
-   currency symbol
-   combo packs and menu data in the app payload

### 3. API-first hot path

Speeder does not depend on Livewire for the hot path.

The Vue app uses Axios against dedicated API endpoints for:

-   order load/save
-   customer search/save
-   order number generation
-   order types
-   delivery platforms
-   phone codes
-   extra charges
-   tables
-   reservations
-   table unlock

The observed routes in `genx-rest` that mirror this pattern are the same class of endpoints that Speeder uses or expects to use in the Vue flow.

### 4. Offline-first queueing

Speeder uses a local queue for offline and retryable mutations.

Observed pieces in `resources/js/PosApp.vue` and its composables:

-   `useOfflineMode.js` for online/offline detection
-   `pendingOperations` queue in localStorage
-   `offlineApiCall()` wrapper to either send immediately or queue the operation
-   `syncPendingOperations()` to replay queued actions when connectivity returns
-   `useCacheStorage.js` for menu/category/item/waiter cache management

This is not just a convenience feature. It is part of the POS methodology:

-   local state updates immediately
-   persistence is deferred
-   the queue is synced later
-   UI does not block on every click

### 5. Componentized UI shell

Speeder splits the POS into reusable Vue components:

-   `MenuPanel`
-   `MenuItem`
-   `OrderPanel`
-   `DiscountModal`
-   `OrderTypeModal`
-   `TableAssignmentModal`
-   `ReservationModal`
-   `AddCustomerModal`
-   `AddNoteModal`
-   `OfflineIndicator`

The layout is a stateful shell with modal-driven workflows instead of server-rendered partials.

### 6. Optimistic mutation methodology

The add/remove/qty workflows are designed for immediate UI feedback:

-   menu item click updates the cart state in the browser
-   quantity changes update the cart item directly
-   item removal updates the list immediately
-   server save happens separately through API or offline queue

This is the central performance advantage.

### 7. Related-feature cohesion

Speeder treats related POS features as one client workflow, not separate server pages:

-   order type selection
-   delivery platform selection
-   table assignment and table change confirmation
-   reservations awareness
-   customer search/create/edit
-   notes and discounts
-   cache clear and offline status

These are all integrated into the same POS client shell.

## Speeder UI Shape

Speeder's visible POS layout is simpler than the current `genx-rest` Vue shell.

Observed UI pattern in `C:\xampp\htdocs\table\resources\js\PosApp.vue` and its components:

-   outer shell is a plain `div` with `flex-grow lg:flex h-auto pt-6`
-   the menu and order panels are rendered directly as siblings
-   the order panel keeps a wider desktop column (`lg:w-6/12`)
-   the menu panel is lean and does not show combo-pack cards in the current Speeder UI
-   the right side header uses a compact toolbar with order type, customer link, receipt number, table display, pax, waiter, and note entry
-   the menu side uses a simple search + reset row, menu filter pills, category filter pills, and an item grid
-   the UI relies on dark/light Tailwind states and compact card spacing, not a heavy shell wrapper

In practice, this means the Speeder UI is cleaner and more minimal than the current `genx-rest` Vue shell, even though the internal component structure is almost the same.

## Current genx-rest Architecture

### 1. POS entry runtime now defaults to Vue

Current `genx-rest` now serves the Vue POS screen as the default surface for:

-   `/pos`
-   `/pos/order/{id}`
-   `/pos/kot/{id}`

These routes now render `resources/views/pos/posvue.blade.php` and mount `resources/js/PosApp.vue`.

Legacy Blade/Livewire POS code still exists in the codebase as a fallback implementation, but it is no longer the primary entry runtime.

### 2. Hybrid queue bridge exists

`genx-rest` now has a client-op queue in Blade that batches operations before they reach the server.

Observed queue path:

-   `queueAddItem()` for add-item actions
-   `queueQtyDelta()` and `queueQtySet()` for quantity changes
-   `queueDeleteItem()` for removal
-   `flushClientOps()` to collapse queued operations
-   `applyClientOps()` in Livewire as the current authoritative server mutation handler

This is an improvement, but it is still not the same as Speeder because Livewire remains the server-side hot-path transport.

### 3. `genx-rest` already has most of the same backend surfaces

The following backend pieces now exist in `genx-rest`:

-   `PosBootstrapService`
-   `PosBootstrapController`
-   `PosSupportController`
-   `PosVueOrderController`
-   `PosCartBatchSyncController`
-   `PosBatchSyncService`
-   `/ajax/pos/bootstrap`
-   `/ajax/pos/client-ops`
-   `/api/pos/bootstrap`
-   `/api/pos/cart/batch-sync`
-   `/api/pos/orders`
-   `/api/pos/orders/{id}`
-   support endpoints for order types, delivery platforms, phone codes, customers, tables, reservations, unlock table, and extra charges

So the backend surface is much closer to Speeder than the Blade hot path is.

### 4. The Vue POS path is now the entry runtime

`genx-rest` has a Vue POS route and mount point:

-   `resources/views/pos/posvue.blade.php`
-   `resources/js/PosApp.vue`
-   `resources/js/pos-app.js`

The Vue app is already structurally similar to Speeder, including:

-   offline queueing
-   cache composables
-   Axios-based API mutation
-   modal-driven workflows
-   order loading/editing

This route family now serves the default POS entry runtime.

### 4.1 Current UI deviations from Speeder

The current `genx-rest` Vue UI is close to Speeder, but it still differs in a few visible ways:

-   the shell wrapper is heavier and more POS-app-specific
-   the menu panel still has combo-pack presentation, which Speeder does not show in the current UI- the default desktop column ratio was more narrow on the order panel
-   some genx-rest UI state was layered in before the Speeder-like baseline was matched

Those differences are mostly presentation, not workflow logic.

### 5. Livewire still owns some behavior that Speeder would keep client-side

`app/Livewire/Pos/Pos.php` still contains the business rules and state transitions for the legacy screen:

-   permissions
-   quantity mutation
-   item removal
-   combo item rules
-   KOT persistence and removal reasons
-   pricing recalculation and tax updates
-   table activity updates

That is fine for legacy support, but it is the architectural gap if the goal is Speeder parity.

## Exact Gap Comparison

| Concern                             | Speeder                                          | genx-rest today                                            | Gap                                               |
| ----------------------------------- | ------------------------------------------------ | ---------------------------------------------------------- | ------------------------------------------------- |
| Default POS runtime                 | Vue-first client shell                           | Vue-first default route family                             | Keep fallback only for controlled rollback        |
| State ownership                     | Cart/UI owned by Vue refs                        | Cart still owned by Livewire on legacy path                | Client state is not the system of record in Blade |
| Hot-path transport                  | Axios/API calls                                  | Livewire queue by default, AJAX only as a transport option | Still not fully API-native                        |
| Add item                            | Immediate client cart mutation                   | Queued in Blade, then Livewire flush                       | Same behavior only on the Vue path                |
| Qty inc/dec                         | Immediate optimistic client update               | Queued client ops, then Livewire flush                     | Transport still legacy on Blade                   |
| Remove item                         | Client-side state update + server sync           | Queued client ops, then Livewire delete logic              | Removal reason / KOT edge cases still server-led  |
| Bootstrap                           | Cached bootstrap service + app bootstrap payload | Same service exists                                        | Largely filled                                    |
| Offline mode                        | Built into client shell                          | Exists in Vue path                                         | Not the default POS path                          |
| Customer/table/order-type workflows | Same shell, same state model                     | Implemented in Vue path                                    | Legacy Blade still separate                       |
| Order save/edit                     | API controller and Vue client                    | API controllers exist; Blade still separate                | Route default and state model differ              |
| KOT order detail/edit               | Client-driven shell                              | Legacy Livewire has detailed KOT logic                     | Exact parity still not unified                    |

## What Is Already Close or Already Done in genx-rest

These areas are already near Speeder behavior:

-   `PosBootstrapService` caches the stable bootstrap payload.
-   `PosBootstrapController` exposes a cached bootstrap endpoint.
-   `PosSupportController` exposes support endpoints for tables, customers, phone codes, reservations, order types, and extra charges.
-   `PosVueOrderController` supports order show/store for the Vue POS.
-   The Vue app already uses offline/cache composables and Axios-driven order flows.
-   The Blade queue bridge now batches add/qty/remove instead of immediately calling per click.

## What Is Still Missing for Exact Speeder Parity

### 1. Make Vue the primary POS runtime

The biggest missing change is route ownership.

To match Speeder, the POS hot path must run through the Vue shell, not through the Livewire screen.

### 2. Treat AJAX/API as the mutation path, not a fallback

Right now the AJAX client-ops surface exists, but the legacy path can still resolve to Livewire.

To match Speeder, add/remove/qty/save must use one client-state and API model end-to-end.

### 3. Remove remaining hot-path dependence on Livewire state

The Blade POS still depends on Livewire for authoritative cart state and a lot of business rules.

For parity, that state needs to move to the client shell, with server validation only at the boundary.

### 4. Unify order edit and KOT edit flows under the same client model

Speeder-style behavior expects loading an existing order to hydrate the same client cart model as a new order.

The legacy Livewire route still has divergent behavior for KOT removal reasons, order detail edits, and combo item protections.

### 5. Finish parity for all special cases

Before the migration is truly complete, the Vue path must cover the same edge cases as the legacy path:

-   combo pack rules
-   quantity floor handling
-   persisted KOT item removals
-   billed/paid order edit restrictions
-   table lock / unlock behavior
-   reservation confirmation flow

### 6. Decide the final default route

Speeder's methodology only works cleanly when the Vue POS is the main user entry point.

In `genx-rest`, Vue POS is now the default entry runtime for normal users. The legacy Blade route is retained as a fallback behind a feature flag for compatibility.

## Recommended Migration Sequence

1. Make the Vue route the default POS entry for normal users.
2. Keep the legacy Blade route as a fallback behind a flag.
3. Use the same bootstrap service and support endpoints for both paths.
4. Keep Livewire only for legacy compatibility and admin fallback during the transition.
5. Remove direct Livewire hot-path calls once Vue parity is validated for:
    - add item
    - qty inc/dec/set
    - remove item
    - order save
    - order load/edit
    - customer/table/order-type changes

## Bottom Line

Speeder is not just a different UI. It is a different ownership model:

-   client owns the cart
-   API owns persistence
-   cache owns bootstrap
-   offline queue owns retry
-   server validates and finalizes, but does not drive every click

`genx-rest` now has much of the same backend support, a matching Vue implementation, and Vue-first default entry routes. The remaining gap is full behavioral parity for all edge cases and safe retirement of legacy-only paths.
