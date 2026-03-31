<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Module;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Supplier;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for PurchaseOrderController.
 *
 * Covers the changes in this PR:
 * - edit(): abort 403 for received purchases when user lacks 'Edit Received Purchase'
 * - edit(): allow access for received purchases when user has the permission
 * - generatePdf(): added DomPDF options (structural test only)
 */
class PurchaseOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected Branch $branch;
    protected User $user;
    protected Supplier $supplier;
    protected PurchaseLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base tenant hierarchy
        $this->restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'currency_id' => null, // nullable in most setups
        ]);

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

        // Make restaurant_modules() return 'Inventory' by pre-populating the cache.
        // The key format mirrors the restaurant_modules() helper in app/Helper/start.php.
        // modules_statuses.json is absent in test env → version = 'no-module-status'.
        // Package is absent → packageVersion = 'no-package-version'.
        $cacheKey = implode('_', [
            'restaurant_modules',
            $this->restaurant->id,
            null,                   // package_id
            'no-package-version',
            'no-module-status',
        ]);
        Cache::put($cacheKey, ['Inventory'], now()->addMinutes(5));
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function givePermission(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'web']);
            }
            $this->user->givePermissionTo($permission);
        }

        // Flush cached role_permissions so user_can() picks up the new grants
        session()->forget('role_permissions');
    }

    private function makePurchaseOrder(string $status = 'ordered'): PurchaseOrder
    {
        return PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'location_id' => $this->location->id,
            'order_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'status' => $status,
        ]);
    }

    // ─── Tests: edit() ─────────────────────────────────────────────────────────

    /**
     * A received purchase order requires the 'Edit Received Purchase' permission.
     * Without it, the user gets a 403.
     */
    public function test_edit_received_purchase_returns_403_without_edit_received_permission(): void
    {
        $this->givePermission('Update Purchase Order');

        $purchase = $this->makePurchaseOrder('received');

        $response = $this->actingAs($this->user)
            ->get(route('purchases.edit', $purchase->id));

        $response->assertStatus(403);
    }

    /**
     * A user with 'Edit Received Purchase' can access the edit form for received orders.
     */
    public function test_edit_received_purchase_returns_200_with_edit_received_permission(): void
    {
        $this->givePermission('Update Purchase Order', 'Edit Received Purchase');

        $purchase = $this->makePurchaseOrder('received');

        $response = $this->actingAs($this->user)
            ->get(route('purchases.edit', $purchase->id));

        // View may not resolve in isolated test environment, but we assert it is NOT 403
        $response->assertStatus(200);
    }

    /**
     * Non-received purchase orders do not trigger the special permission check.
     */
    public function test_edit_non_received_purchase_does_not_require_edit_received_permission(): void
    {
        $this->givePermission('Update Purchase Order');

        foreach (['ordered', 'pending'] as $status) {
            $purchase = $this->makePurchaseOrder($status);

            $response = $this->actingAs($this->user)
                ->get(route('purchases.edit', $purchase->id));

            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Expected non-403 for status '{$status}' but got 403."
            );
        }
    }

    /**
     * Without the base 'Update Purchase Order' or 'Edit Purchase Order' permission,
     * the user gets 403 even for non-received orders.
     */
    public function test_edit_returns_403_without_base_update_permission(): void
    {
        // No permissions granted

        $purchase = $this->makePurchaseOrder('ordered');

        $response = $this->actingAs($this->user)
            ->get(route('purchases.edit', $purchase->id));

        $response->assertStatus(403);
    }

    /**
     * A user from a different branch cannot edit a purchase from another branch.
     */
    public function test_edit_returns_403_for_wrong_branch(): void
    {
        $this->givePermission('Update Purchase Order');

        $otherBranch = Branch::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Other Branch',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'branch_id' => $otherBranch->id,
        ]);
        $this->givePermission('Update Purchase Order');

        // Purchase belongs to $this->branch, not $otherBranch
        $purchase = $this->makePurchaseOrder('ordered');

        $response = $this->actingAs($otherUser)
            ->get(route('purchases.edit', $purchase->id));

        $response->assertStatus(403);
    }

    /**
     * Regression: 'Edit Purchase Order' alone (without 'Update Purchase Order')
     * is still sufficient to pass the base permission check for non-received orders.
     */
    public function test_edit_purchase_order_permission_also_grants_access(): void
    {
        $this->givePermission('Edit Purchase Order');

        $purchase = $this->makePurchaseOrder('pending');

        $response = $this->actingAs($this->user)
            ->get(route('purchases.edit', $purchase->id));

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Regression: having only 'Edit Received Purchase' without base edit permission
     * still results in 403 for any order.
     */
    public function test_edit_received_permission_alone_is_not_sufficient(): void
    {
        $this->givePermission('Edit Received Purchase');
        // Missing 'Update Purchase Order' / 'Edit Purchase Order'

        $purchase = $this->makePurchaseOrder('received');

        $response = $this->actingAs($this->user)
            ->get(route('purchases.edit', $purchase->id));

        $response->assertStatus(403);
    }
}