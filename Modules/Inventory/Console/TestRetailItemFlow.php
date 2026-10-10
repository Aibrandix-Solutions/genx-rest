<?php

namespace Modules\Inventory\Console;

use App\Events\NewOrderCreated;
use App\Models\Branch;
use App\Models\ItemCategory;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Scopes\BranchScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderItem;
use Modules\Inventory\Entities\Recipe;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Listeners\UpdateInventoryOnOrderReceived;

class TestRetailItemFlow extends Command
{
    protected $signature = 'inventory:test-retail-flow
                            {--branch-id= : Branch to run the simulation on}
                            {--purchase-qty=24 : Units to receive on purchase}
                            {--sale-qty=3 : Units to sell on the test order}
                            {--keep-data : Do not remove test records after the run}';

    protected $description = 'Simulate purchase → stock → POS sale for a retail SKU (e.g. Water Bottle 100ml) and print stock before/after';

    /** @var array<string, mixed> */
    private array $created = [];

    private string $runTag = '';

    public function handle(): int
    {
        $this->runTag = 'TEST-WB100-' . now()->format('YmdHis');

        $branch = $this->resolveBranch();
        if (! $branch) {
            $this->error('No branch found. Pass --branch-id= or ensure branches exist.');

            return self::FAILURE;
        }

        $restaurant = Restaurant::find($branch->restaurant_id);
        if (! $restaurant) {
            $this->error('Restaurant not found for branch #' . $branch->id);

            return self::FAILURE;
        }

        session(['branch' => $branch, 'restaurant' => $restaurant]);

        $purchaseQty = max(1, (float) $this->option('purchase-qty'));
        $saleQty = max(1, (int) $this->option('sale-qty'));

        $this->info('Retail flow simulation');
        $this->line('Run tag: ' . $this->runTag);
        $this->line('Branch: #' . $branch->id . ' — ' . $branch->name);
        $this->line('Restaurant: #' . $restaurant->id . ' — ' . $restaurant->name);
        $this->newLine();

        try {
            DB::beginTransaction();

            $fixtures = $this->createFixtures($branch, $restaurant);
            $inventoryItem = $fixtures['inventory_item'];
            $menuItem = $fixtures['menu_item'];
            $location = $fixtures['location'];
            $unit = $fixtures['unit'];

            $stockBefore = $this->branchStockTotal($branch->id, $inventoryItem->id);
            $this->table(['Step', 'Value'], [
                ['Stock before purchase', (string) $stockBefore],
            ]);

            $purchase = $this->receivePurchase(
                $branch,
                $fixtures['supplier'],
                $location,
                $inventoryItem,
                $unit,
                $purchaseQty
            );

            $stockAfterPurchase = $this->branchStockTotal($branch->id, $inventoryItem->id);
            $this->info('Purchase received (PO ' . $purchase->po_number . ', qty ' . $purchaseQty . ')');
            $this->line('Stock after purchase: ' . $stockAfterPurchase . ' (expected ' . ($stockBefore + $purchaseQty) . ')');

            $order = $this->createBilledOrder($branch, $menuItem, $saleQty);
            $this->info('Test order #' . $order->id . ' billed with qty ' . $saleQty);

            app(UpdateInventoryOnOrderReceived::class)->handle(new NewOrderCreated($order));

            $stockAfterSale = $this->branchStockTotal($branch->id, $inventoryItem->id);
            $expectedAfterSale = $stockAfterPurchase - ($fixtures['recipe_qty'] * $saleQty);

            $this->newLine();
            $this->table(['Metric', 'Actual', 'Expected'], [
                ['Stock after sale', (string) $stockAfterSale, (string) $expectedAfterSale],
                ['Menu in_stock', $menuItem->fresh()->in_stock ? '1' : '0', $stockAfterSale > 0 ? '1' : '0 or 0 if depleted'],
            ]);

            $ok = abs($stockAfterSale - $expectedAfterSale) < 0.0001;
            if ($ok) {
                $this->info('PASS — stock decreased by recipe qty × sale qty.');
            } else {
                $this->warn('CHECK — stock mismatch. Ensure a recipe exists (qty 1) and stock exists on this branch.');
            }

            if ($this->option('keep-data')) {
                DB::commit();
                $this->comment('Test data kept (--keep-data). PO: ' . $purchase->po_number . ', Order: #' . $order->id);
            } else {
                DB::rollBack();
                $this->comment('Transaction rolled back — no permanent changes (default). Use --keep-data to persist test rows.');
            }

            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Simulation failed: ' . $e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());

            return self::FAILURE;
        }
    }

    private function resolveBranch(): ?Branch
    {
        $branchId = $this->option('branch-id');
        if ($branchId) {
            return Branch::find($branchId);
        }

        return Branch::query()->orderBy('id')->first();
    }

    /**
     * @return array{inventory_item: InventoryItem, menu_item: MenuItem, location: PurchaseLocation, supplier: Supplier, unit: Unit, recipe_qty: float}
     */
    private function createFixtures(Branch $branch, Restaurant $restaurant): array
    {
        $unit = Unit::query()->where('symbol', 'pc')->first()
            ?? Unit::query()->where('name', 'Piece')->first();

        if (! $unit) {
            $unitPayload = ['name' => 'Piece', 'symbol' => 'pc'];
            if (Schema::hasColumn('units', 'branch_id')) {
                $unitPayload['branch_id'] = $branch->id;
            }
            $unit = Unit::create($unitPayload);
            $this->created['unit_id'] = $unit->id;
        }

        $invCategoryQuery = InventoryItemCategory::query();
        if (Schema::hasColumn('inventory_item_categories', 'restaurant_id')) {
            $invCategoryQuery->where('restaurant_id', $restaurant->id);
        } elseif (Schema::hasColumn('inventory_item_categories', 'branch_id')) {
            $invCategoryQuery->where('branch_id', $branch->id);
        }
        $invCategory = $invCategoryQuery->first();

        if (! $invCategory) {
            $categoryPayload = ['name' => $this->runTag . ' Beverages'];
            if (Schema::hasColumn('inventory_item_categories', 'restaurant_id')) {
                $categoryPayload['restaurant_id'] = $restaurant->id;
            }
            if (Schema::hasColumn('inventory_item_categories', 'branch_id')) {
                $categoryPayload['branch_id'] = $branch->id;
            }
            $invCategory = InventoryItemCategory::create($categoryPayload);
            $this->created['inv_category_id'] = $invCategory->id;
        }

        $supplier = Supplier::query()
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if (! $supplier) {
            $supplier = Supplier::create([
                'restaurant_id' => $restaurant->id,
                'name' => $this->runTag . ' Supplier',
            ]);
            $this->created['supplier_id'] = $supplier->id;
        }

        $inventoryItem = InventoryItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Water Bottle 100ml (' . $this->runTag . ')',
            'item_code' => $this->runTag,
            'inventory_item_category_id' => $invCategory->id,
            'unit_id' => $unit->id,
            'unit_purchase_price' => 50,
            'threshold_quantity' => 5,
            'preferred_supplier_id' => $supplier->id,
            'is_active' => true,
        ]);
        $this->created['inventory_item_id'] = $inventoryItem->id;

        $location = PurchaseLocation::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('branch_id', $branch->id)
            ->where('type', 'branch')
            ->first();

        if (! $location) {
            $location = PurchaseLocation::create([
                'restaurant_id' => $restaurant->id,
                'name' => $branch->name . ' Store',
                'type' => 'branch',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);
            $this->created['location_id'] = $location->id;
        }

        $menu = Menu::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branch->id)
            ->first();

        if (! $menu) {
            $menu = Menu::create([
                'branch_id' => $branch->id,
                'menu_name' => $this->runTag . ' Menu',
            ]);
            $this->created['menu_id'] = $menu->id;
        }

        $itemCategory = ItemCategory::query()
            ->where('branch_id', $branch->id)
            ->first();

        if (! $itemCategory) {
            $itemCategory = ItemCategory::create([
                'branch_id' => $branch->id,
                'category_name' => $this->runTag . ' Retail',
            ]);
            $this->created['item_category_id'] = $itemCategory->id;
        }

        $menuItem = MenuItem::withoutGlobalScope(BranchScope::class)->create([
            'branch_id' => $branch->id,
            'menu_id' => $menu->id,
            'item_category_id' => $itemCategory->id,
            'item_name' => 'Water Bottle 100ml (' . $this->runTag . ')',
            'type' => MenuItem::VEG,
            'price' => 120,
            'in_stock' => 1,
        ]);
        $this->created['menu_item_id'] = $menuItem->id;

        $recipeQty = 1.0;
        Recipe::create([
            'menu_item_id' => $menuItem->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => $recipeQty,
            'unit_id' => $unit->id,
        ]);
        $this->created['recipe'] = true;

        return [
            'inventory_item' => $inventoryItem,
            'menu_item' => $menuItem,
            'location' => $location,
            'supplier' => $supplier,
            'unit' => $unit,
            'recipe_qty' => $recipeQty,
        ];
    }

    private function branchStockTotal(int $branchId, int $inventoryItemId): float
    {
        return (float) InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('inventory_item_id', $inventoryItemId)
            ->sum('quantity');
    }

    private function receivePurchase(
        Branch $branch,
        Supplier $supplier,
        PurchaseLocation $location,
        InventoryItem $inventoryItem,
        Unit $unit,
        float $quantity
    ): PurchaseOrder {
        $unitPrice = 50.0;
        $subtotal = $quantity * $unitPrice;

        $purchase = PurchaseOrder::withoutGlobalScope(BranchScope::class)->create([
            'po_number' => $this->runTag . '-PO',
            'branch_id' => $branch->id,
            'location_id' => $location->id,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'total_amount' => $subtotal,
            'status' => 'received',
            'created_by' => null,
        ]);
        $this->created['purchase_order_id'] = $purchase->id;

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchase->id,
            'inventory_item_id' => $inventoryItem->id,
            'unit_id' => $unit->id,
            'quantity' => $quantity,
            'received_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ]);

        $targetBranchId = $location->type === 'branch' && $location->branch_id
            ? $location->branch_id
            : $branch->id;

        $stock = InventoryStock::firstOrCreate(
            [
                'inventory_item_id' => $inventoryItem->id,
                'branch_id' => $targetBranchId,
                'location_id' => $location->id,
            ],
            ['quantity' => 0]
        );
        $stock->increment('quantity', $quantity);

        InventoryMovement::create([
            'branch_id' => $targetBranchId,
            'location_id' => $location->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => $quantity,
            'transaction_type' => 'in',
            'unit_purchase_price' => $unitPrice,
            'supplier_id' => $supplier->id,
            'added_by' => null,
        ]);

        return $purchase;
    }

    private function createBilledOrder(Branch $branch, MenuItem $menuItem, int $quantity): Order
    {
        $lineAmount = (float) $menuItem->price * $quantity;

        $order = Order::withoutGlobalScope(BranchScope::class)->create([
            'branch_id' => $branch->id,
            'order_number' => $this->runTag . '-ORD',
            'date_time' => now(),
            'status' => 'billed',
            'sub_total' => $lineAmount,
            'total' => $lineAmount,
            'amount_paid' => 0,
            'placed_via' => 'pos',
        ]);
        $this->created['order_id'] = $order->id;

        OrderItem::withoutGlobalScope(BranchScope::class)->create([
            'branch_id' => $branch->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => $quantity,
            'price' => $menuItem->price,
            'amount' => $lineAmount,
        ]);

        return $order->fresh(['items']);
    }
}
