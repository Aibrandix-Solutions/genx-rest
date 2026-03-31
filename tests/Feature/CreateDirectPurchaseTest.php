<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderItem;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Livewire\CreateDirectPurchase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for the CreateDirectPurchase Livewire component.
 *
 * Covers changes in this PR:
 * - makePurchaseItemRow() includes 'last_purchase_price' => null
 * - openQuickAddModal() / closeQuickAddModal() state management
 * - saveQuickAddItem() creates InventoryItem and adds to items list
 * - searchItems() now decorates results with last_purchase_price
 * - selectItem() stores last_purchase_price on the item row
 * - updateItemPrice() fetches and stores last_purchase_price
 * - createInventoryMovements() includes location_id in InventoryMovement
 */
class CreateDirectPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected Branch $branch;
    protected User $user;
    protected Supplier $supplier;
    protected PurchaseLocation $location;
    protected InventoryItemCategory $category;
    protected Unit $unit;

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
    }

    // ─── makePurchaseItemRow ────────────────────────────────────────────────────

    /**
     * addItem() produces a row with the new 'last_purchase_price' key set to null.
     */
    public function test_add_item_includes_last_purchase_price_key(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->call('addItem')
            ->assertSet('items', function ($items) {
                // At least one item should have 'last_purchase_price' set to null
                foreach ($items as $item) {
                    if (array_key_exists('last_purchase_price', $item) && $item['last_purchase_price'] === null) {
                        return true;
                    }
                }
                return false;
            });
    }

    // ─── openQuickAddModal ──────────────────────────────────────────────────────

    /**
     * openQuickAddModal() sets showQuickAddModal to true and copies searchItem to quickAddName.
     */
    public function test_open_quick_add_modal_sets_state(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('searchItem', 'Chicken')
            ->call('openQuickAddModal')
            ->assertSet('showQuickAddModal', true)
            ->assertSet('quickAddName', 'Chicken')
            ->assertSet('showSearchResults', false);
    }

    /**
     * openQuickAddModal() resets all quick-add fields to their defaults.
     */
    public function test_open_quick_add_modal_resets_other_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddCategoryId', '999')
            ->set('quickAddUnitId', '888')
            ->set('quickAddPrice', 50)
            ->set('quickAddThresholdQuantity', 10)
            ->call('openQuickAddModal')
            ->assertSet('quickAddCategoryId', '')
            ->assertSet('quickAddUnitId', '')
            ->assertSet('quickAddPrice', 0)
            ->assertSet('quickAddThresholdQuantity', 0);
    }

    // ─── closeQuickAddModal ─────────────────────────────────────────────────────

    /**
     * closeQuickAddModal() hides the modal and resets all quick-add properties.
     */
    public function test_close_quick_add_modal_resets_state(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddName', 'Beef')
            ->set('quickAddCategoryId', '1')
            ->set('quickAddUnitId', '1')
            ->set('quickAddPrice', 25)
            ->set('quickAddThresholdQuantity', 5)
            ->set('showQuickAddModal', true)
            ->call('closeQuickAddModal')
            ->assertSet('showQuickAddModal', false)
            ->assertSet('quickAddName', '')
            ->assertSet('quickAddCategoryId', '')
            ->assertSet('quickAddUnitId', '')
            ->assertSet('quickAddPrice', 0)
            ->assertSet('quickAddThresholdQuantity', 0);
    }

    // ─── saveQuickAddItem ───────────────────────────────────────────────────────

    /**
     * saveQuickAddItem() creates a new InventoryItem and adds it to the items list.
     */
    public function test_save_quick_add_item_creates_inventory_item(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddName', 'New Ingredient')
            ->set('quickAddCategoryId', $this->category->id)
            ->set('quickAddUnitId', $this->unit->id)
            ->set('quickAddPrice', 12.50)
            ->set('quickAddThresholdQuantity', 5)
            ->call('saveQuickAddItem');

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'New Ingredient',
            'restaurant_id' => $this->restaurant->id,
            'inventory_item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'unit_purchase_price' => 12.50,
            'threshold_quantity' => 5,
        ]);
    }

    /**
     * After saveQuickAddItem(), the modal is closed and searchItem is cleared.
     */
    public function test_save_quick_add_item_closes_modal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddName', 'New Ingredient')
            ->set('quickAddCategoryId', $this->category->id)
            ->set('quickAddUnitId', $this->unit->id)
            ->set('quickAddPrice', 5.00)
            ->set('quickAddThresholdQuantity', 0)
            ->call('saveQuickAddItem')
            ->assertSet('showQuickAddModal', false)
            ->assertSet('searchItem', '')
            ->assertSet('filteredItems', [])
            ->assertSet('showSearchResults', false);
    }

    /**
     * saveQuickAddItem() adds the newly created item to the items list.
     */
    public function test_save_quick_add_item_adds_item_to_list(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddName', 'Pineapple')
            ->set('quickAddCategoryId', $this->category->id)
            ->set('quickAddUnitId', $this->unit->id)
            ->set('quickAddPrice', 3.00)
            ->set('quickAddThresholdQuantity', 0)
            ->call('saveQuickAddItem');

        $newItem = InventoryItem::where('name', 'Pineapple')->first();
        $this->assertNotNull($newItem);

        $items = $component->get('items');
        $found = collect($items)->first(fn($i) => ($i['inventory_item_id'] ?? null) == $newItem->id);
        $this->assertNotNull($found, 'Newly created item should appear in the items list.');
        $this->assertNull($found['last_purchase_price']);
    }

    /**
     * saveQuickAddItem() with missing required fields produces validation errors.
     */
    public function test_save_quick_add_item_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddName', '')
            ->call('saveQuickAddItem')
            ->assertHasErrors(['quickAddName']);
    }

    /**
     * saveQuickAddItem() is idempotent against double-submission (quickAddSaving guard).
     */
    public function test_save_quick_add_item_prevents_double_submission(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(CreateDirectPurchase::class)
            ->set('quickAddSaving', true)
            ->set('quickAddName', 'DoubleSave')
            ->set('quickAddCategoryId', $this->category->id)
            ->set('quickAddUnitId', $this->unit->id)
            ->set('quickAddPrice', 5.00)
            ->set('quickAddThresholdQuantity', 0)
            ->call('saveQuickAddItem');

        // Nothing should have been created
        $this->assertDatabaseMissing('inventory_items', ['name' => 'DoubleSave']);
    }

    // ─── searchItems / last_purchase_price ─────────────────────────────────────

    /**
     * searchItems() decorates results with last_purchase_price from PurchaseOrderItem.
     */
    public function test_search_items_includes_last_purchase_price(): void
    {
        $this->actingAs($this->user);

        $inventoryItem = InventoryItem::create([
            'name' => 'Flour',
            'restaurant_id' => $this->restaurant->id,
            'inventory_item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'unit_purchase_price' => 2.00,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'location_id' => $this->location->id,
            'order_date' => now()->toDateString(),
            'total_amount' => 20.00,
            'status' => 'received',
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 10,
            'unit_price' => 2.50,
            'subtotal' => 25.00,
        ]);

        Livewire::test(CreateDirectPurchase::class)
            ->set('searchItem', 'Flour')
            ->call('searchItems')
            ->assertSet('showSearchResults', true)
            ->assertSet('filteredItems', function ($items) {
                foreach ($items as $item) {
                    if ($item->name === 'Flour' && (float) $item->last_purchase_price === 2.50) {
                        return true;
                    }
                }
                return false;
            });
    }

    /**
     * searchItems() returns null last_purchase_price for items with no purchase history.
     */
    public function test_search_items_last_purchase_price_null_when_no_history(): void
    {
        $this->actingAs($this->user);

        InventoryItem::create([
            'name' => 'BrandNewItem',
            'restaurant_id' => $this->restaurant->id,
            'inventory_item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'unit_purchase_price' => 1.00,
        ]);

        Livewire::test(CreateDirectPurchase::class)
            ->set('searchItem', 'BrandNewItem')
            ->call('searchItems')
            ->assertSet('filteredItems', function ($items) {
                foreach ($items as $item) {
                    if ($item->name === 'BrandNewItem') {
                        return $item->last_purchase_price === null;
                    }
                }
                return false;
            });
    }

    /**
     * searchItems() with an empty term returns no results and hides the dropdown.
     */
    public function test_search_items_empty_term_clears_results(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateDirectPurchase::class)
            ->set('searchItem', '')
            ->call('searchItems')
            ->assertSet('filteredItems', [])
            ->assertSet('showSearchResults', false);
    }

    // ─── selectItem ────────────────────────────────────────────────────────────

    /**
     * selectItem() stores the last_purchase_price on the item row.
     */
    public function test_select_item_stores_last_purchase_price(): void
    {
        $this->actingAs($this->user);

        $inventoryItem = InventoryItem::create([
            'name' => 'Rice',
            'restaurant_id' => $this->restaurant->id,
            'inventory_item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'unit_purchase_price' => 3.00,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TEST-RICE',
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'location_id' => $this->location->id,
            'order_date' => now()->toDateString(),
            'total_amount' => 30.00,
            'status' => 'received',
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 10,
            'unit_price' => 3.20,
            'subtotal' => 32.00,
        ]);

        $component = Livewire::test(CreateDirectPurchase::class)
            ->call('selectItem', $inventoryItem->id);

        $items = $component->get('items');
        $found = collect($items)->first(fn($i) => ($i['inventory_item_id'] ?? null) == $inventoryItem->id);

        $this->assertNotNull($found);
        $this->assertEquals(3.20, $found['last_purchase_price']);
    }

    /**
     * selectItem() clears the search after selection.
     */
    public function test_select_item_clears_search(): void
    {
        $this->actingAs($this->user);

        $inventoryItem = InventoryItem::create([
            'name' => 'Sugar',
            'restaurant_id' => $this->restaurant->id,
            'inventory_item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'unit_purchase_price' => 1.50,
        ]);

        Livewire::test(CreateDirectPurchase::class)
            ->set('searchItem', 'Sugar')
            ->set('showSearchResults', true)
            ->call('selectItem', $inventoryItem->id)
            ->assertSet('searchItem', '')
            ->assertSet('filteredItems', [])
            ->assertSet('showSearchResults', false);
    }

    // ─── removeItem ────────────────────────────────────────────────────────────

    /**
     * removeItem() removes the item at the given index and re-indexes the array.
     */
    public function test_remove_item_shrinks_list(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(CreateDirectPurchase::class)
            ->call('addItem')
            ->call('addItem');

        $initialCount = count($component->get('items'));

        $component->call('removeItem', 0);

        $this->assertCount($initialCount - 1, $component->get('items'));
    }

    /**
     * removeItem() re-adds an empty item when the list would become empty.
     */
    public function test_remove_last_item_adds_placeholder(): void
    {
        $this->actingAs($this->user);

        // Component starts with one item added in mount()
        $component = Livewire::test(CreateDirectPurchase::class);
        $count = count($component->get('items'));
        $this->assertGreaterThanOrEqual(1, $count);

        // Remove all items
        for ($i = 0; $i < $count; $i++) {
            $component->call('removeItem', 0);
        }

        $this->assertCount(1, $component->get('items'));
    }
}