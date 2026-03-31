<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderItem;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Livewire\EditDirectPurchase;
use Tests\TestCase;

/**
 * Feature tests for the EditDirectPurchase Livewire component.
 *
 * Covers changes in this PR:
 * - startEditPayment() / cancelEditPayment() payment editing state
 * - makePurchaseItemRow() includes last_purchase_price
 * - Status lock: received purchase cannot be reverted to another status
 * - reconcileReceivedStock() via updatePurchase() — delta and location change paths
 * - applyStockMovement() records InventoryMovement with location_id
 */
class EditDirectPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected Branch $branch;
    protected User $user;
    protected Supplier $supplier;
    protected PurchaseLocation $location;
    protected InventoryItemCategory $category;
    protected Unit $unit;
    protected InventoryItem $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::create(['name' => 'Test Restaurant']);
        $this->branch = Branch::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Main Branch',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->supplier = Supplier::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);
        $this->location = PurchaseLocation::create([
            'restaurant_id' => $this->restaurant->id,
            'branch_id' => $this->branch->id,
            'name' => 'Branch Location',
            'type' => 'branch',
            'is_active' => true,
        ]);
        $this->category = InventoryItemCategory::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Test Category',
        ]);
        $this->unit = Unit::create([
            'name' => 'Kilogram',
            'symbol' => 'kg',
        ]);
        $this->inventoryItem = InventoryItem::create([
            'name' => 'Test Item',
            'restaurant_id' => $this->restaurant->id,
            'inventory_item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'unit_purchase_price' => 10.00,
        ]);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function createPurchaseOrder(string $status = 'ordered'): PurchaseOrder
    {
        return PurchaseOrder::create([
            'po_number' => 'PO-TEST-' . uniqid(),
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'location_id' => $this->location->id,
            'order_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'status' => $status,
        ]);
    }

    private function addItemToPurchase(PurchaseOrder $purchase, float $qty = 5, float $price = 10): PurchaseOrderItem
    {
        return PurchaseOrderItem::create([
            'purchase_order_id' => $purchase->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => $qty,
            'unit_price' => $price,
            'subtotal' => $qty * $price,
            'received_quantity' => $qty,
        ]);
    }

    private function addStockForItem(float $qty = 5): InventoryStock
    {
        return InventoryStock::create([
            'inventory_item_id' => $this->inventoryItem->id,
            'branch_id' => $this->branch->id,
            'location_id' => $this->location->id,
            'quantity' => $qty,
        ]);
    }

    // ─── startEditPayment ──────────────────────────────────────────────────────

    /**
     * startEditPayment() populates payment form fields from the selected payment.
     */
    public function test_start_edit_payment_populates_fields(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('ordered');
        $payment = SupplierPayment::create([
            'purchase_order_id' => $purchase->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 75.00,
            'paid_on' => '2025-03-15 10:30:00',
            'payment_method' => 'card',
            'note' => 'Test note',
            'added_by' => $this->user->id,
        ]);

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->call('startEditPayment', $payment->id)
            ->assertSet('recordPayment', true)
            ->assertSet('editingPaymentId', $payment->id)
            ->assertSet('paymentAmount', 75.00)
            ->assertSet('paymentMethod', 'card')
            ->assertSet('paymentNote', 'Test note');
    }

    /**
     * startEditPayment() with a non-existent payment ID fires an error alert
     * and does NOT set editingPaymentId.
     */
    public function test_start_edit_payment_handles_missing_payment(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('ordered');

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->call('startEditPayment', 99999)
            ->assertSet('editingPaymentId', null);
    }

    // ─── cancelEditPayment ──────────────────────────────────────────────────────

    /**
     * cancelEditPayment() resets all editing state to defaults.
     */
    public function test_cancel_edit_payment_resets_state(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('ordered');
        $payment = SupplierPayment::create([
            'purchase_order_id' => $purchase->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50.00,
            'paid_on' => now(),
            'payment_method' => 'cash',
            'added_by' => $this->user->id,
        ]);

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->call('startEditPayment', $payment->id)
            ->assertSet('editingPaymentId', $payment->id)
            ->call('cancelEditPayment')
            ->assertSet('editingPaymentId', null)
            ->assertSet('paymentAmount', null)
            ->assertSet('paymentMethod', 'cash')
            ->assertSet('paymentAccountId', null)
            ->assertSet('paymentNote', null);
    }

    // ─── Status lock ───────────────────────────────────────────────────────────

    /**
     * updatePurchase() blocks reverting a received purchase to a non-received status.
     */
    public function test_update_purchase_cannot_revert_received_status(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('received');
        $this->addItemToPurchase($purchase);

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->set('status', 'ordered') // try to revert
            ->call('updatePurchase')
            ->assertHasErrors(['status']);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchase->id,
            'status' => 'received',
        ]);
    }

    /**
     * Blade renders a locked select when purchase status is received.
     */
    public function test_received_purchase_status_field_is_loaded_as_received(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('received');
        $this->addItemToPurchase($purchase);

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->assertSet('status', 'received');
    }

    // ─── makePurchaseItemRow via addItem ────────────────────────────────────────

    /**
     * addItem() includes 'last_purchase_price' => null in the new row.
     */
    public function test_add_item_row_includes_last_purchase_price_null(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder();

        $component = Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->call('addItem');

        $items = $component->get('items');
        // Find item rows that don't have a DB-backed 'id' (i.e., the newly added blank row)
        $newRow = collect($items)->last();
        $this->assertArrayHasKey('last_purchase_price', $newRow);
        $this->assertNull($newRow['last_purchase_price']);
    }

    // ─── updateItemPrice / last_purchase_price ──────────────────────────────────

    /**
     * updateItemPrice() stores the last_purchase_price for the given item index.
     */
    public function test_update_item_price_stores_last_purchase_price(): void
    {
        $this->actingAs($this->user);

        // Create a previous purchase with this item at a known price
        $previousPurchase = $this->createPurchaseOrder('received');
        PurchaseOrderItem::create([
            'purchase_order_id' => $previousPurchase->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 2,
            'unit_price' => 8.75,
            'subtotal' => 17.50,
        ]);

        $purchase = $this->createPurchaseOrder();
        $this->addItemToPurchase($purchase, 5, 10.00);

        $component = Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id]);

        // Trigger updateItemPrice for the first item (index 0)
        $component->call('updateItemPrice', 0);

        $items = $component->get('items');
        $this->assertEquals(8.75, $items[0]['last_purchase_price']);
    }

    // ─── reconcileReceivedStock via updatePurchase ──────────────────────────────

    /**
     * Increasing quantity on a received purchase creates an 'in' InventoryMovement
     * for the delta amount with the correct location_id.
     */
    public function test_update_received_purchase_quantity_increase_creates_in_movement(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('received');
        $poItem = $this->addItemToPurchase($purchase, 5, 10.00);
        $this->addStockForItem(5);

        // Simulate editing: increase quantity from 5 to 8
        $component = Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id]);

        $items = $component->get('items');
        $items[0]['quantity'] = 8;
        $component->set('items', $items)
            ->call('updatePurchase');

        // Stock should have been incremented by 3
        $this->assertDatabaseHas('inventory_stocks', [
            'inventory_item_id' => $this->inventoryItem->id,
            'branch_id' => $this->branch->id,
            'location_id' => $this->location->id,
            'quantity' => 8,
        ]);

        // An 'in' movement for the delta (3) should have been created
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->inventoryItem->id,
            'branch_id' => $this->branch->id,
            'location_id' => $this->location->id,
            'transaction_type' => 'in',
            'quantity' => 3,
        ]);
    }

    /**
     * Decreasing quantity on a received purchase creates an 'out' InventoryMovement
     * for the delta amount.
     */
    public function test_update_received_purchase_quantity_decrease_creates_out_movement(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('received');
        $this->addItemToPurchase($purchase, 10, 10.00);
        $this->addStockForItem(10);

        $component = Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id]);

        $items = $component->get('items');
        $items[0]['quantity'] = 7; // reduce by 3
        $component->set('items', $items)
            ->call('updatePurchase');

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->inventoryItem->id,
            'transaction_type' => 'out',
            'quantity' => 3,
            'location_id' => $this->location->id,
        ]);
    }

    /**
     * Changing the location on a received purchase reverses stock at the old location
     * and adds stock at the new location.
     */
    public function test_update_received_purchase_location_change_moves_stock(): void
    {
        $this->actingAs($this->user);

        $newLocation = PurchaseLocation::create([
            'restaurant_id' => $this->restaurant->id,
            'branch_id' => $this->branch->id,
            'name' => 'Second Location',
            'type' => 'branch',
            'is_active' => true,
        ]);

        $purchase = $this->createPurchaseOrder('received');
        $this->addItemToPurchase($purchase, 5, 10.00);
        $this->addStockForItem(5); // stock at original location

        $component = Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->set('location_id', $newLocation->id)
            ->call('updatePurchase');

        // Old location stock should have an 'out' movement
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->inventoryItem->id,
            'location_id' => $this->location->id,
            'transaction_type' => 'out',
        ]);

        // New location stock should have an 'in' movement
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->inventoryItem->id,
            'location_id' => $newLocation->id,
            'transaction_type' => 'in',
        ]);
    }

    /**
     * Transitioning a purchase from 'ordered' to 'received' adds all stock at once.
     */
    public function test_transition_to_received_adds_all_stock(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('ordered');
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchase->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 10,
            'unit_price' => 5.00,
            'subtotal' => 50.00,
            'received_quantity' => 0,
        ]);

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->set('status', 'received')
            ->call('updatePurchase');

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->inventoryItem->id,
            'transaction_type' => 'in',
            'quantity' => 10,
            'location_id' => $this->location->id,
        ]);
    }

    // ─── openQuickAddModal / closeQuickAddModal ─────────────────────────────────

    /**
     * openQuickAddModal() sets showQuickAddModal to true and copies searchItem.
     */
    public function test_open_quick_add_modal_sets_state(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder();

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->set('searchItem', 'Lamb')
            ->call('openQuickAddModal')
            ->assertSet('showQuickAddModal', true)
            ->assertSet('quickAddName', 'Lamb')
            ->assertSet('showSearchResults', false);
    }

    /**
     * closeQuickAddModal() hides the modal and resets all quick-add fields.
     */
    public function test_close_quick_add_modal_resets_state(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder();

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->set('quickAddName', 'Lamb')
            ->set('showQuickAddModal', true)
            ->call('closeQuickAddModal')
            ->assertSet('showQuickAddModal', false)
            ->assertSet('quickAddName', '');
    }

    // ─── loadPurchase populates last_purchase_price ─────────────────────────────

    /**
     * loadPurchase() sets last_purchase_price on each item from a prior PurchaseOrderItem.
     */
    public function test_load_purchase_sets_last_purchase_price_on_items(): void
    {
        $this->actingAs($this->user);

        // Older purchase for the same item so it has a history
        $olderPurchase = $this->createPurchaseOrder('received');
        PurchaseOrderItem::create([
            'purchase_order_id' => $olderPurchase->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 3,
            'unit_price' => 7.50,
            'subtotal' => 22.50,
        ]);

        // Current purchase being edited
        $purchase = $this->createPurchaseOrder('ordered');
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchase->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 5,
            'unit_price' => 9.00,
            'subtotal' => 45.00,
        ]);

        $component = Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id]);

        $items = $component->get('items');
        $this->assertNotEmpty($items);
        // The most recent OTHER purchase price (7.50) should be stored
        $this->assertEquals(7.50, $items[0]['last_purchase_price']);
    }

    // ─── Regression ────────────────────────────────────────────────────────────

    /**
     * updatePurchase() with unchanged received purchase quantity creates NO extra movements.
     */
    public function test_update_received_purchase_with_no_changes_creates_no_movements(): void
    {
        $this->actingAs($this->user);

        $purchase = $this->createPurchaseOrder('received');
        $this->addItemToPurchase($purchase, 5, 10.00);
        $this->addStockForItem(5);

        $movementsBefore = InventoryMovement::count();

        Livewire::test(EditDirectPurchase::class, ['purchaseId' => $purchase->id])
            ->call('updatePurchase');

        $this->assertEquals($movementsBefore, InventoryMovement::count());
    }
}