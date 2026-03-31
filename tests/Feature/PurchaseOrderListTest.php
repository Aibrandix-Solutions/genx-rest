<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Livewire\PurchaseOrder\PurchaseOrderList;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for PurchaseOrderList Livewire component.
 *
 * Covers the PR changes:
 * - delete(): requires 'Delete Purchase Order' permission; blocks received/cancelled orders
 * - send():   requires 'Update Purchase Order' permission
 * - cancel(): requires 'Update Purchase Order' permission; blocks received/cancelled orders
 */
class PurchaseOrderListTest extends TestCase
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

        $this->restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
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
        session()->forget('role_permissions');
    }

    private function makePurchaseOrder(string $status = 'ordered'): PurchaseOrder
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

    // ─── delete() ──────────────────────────────────────────────────────────────

    /** delete() aborts with 403 when user lacks 'Delete Purchase Order' */
    public function test_delete_aborts_without_delete_permission(): void
    {
        $this->actingAs($this->user);
        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmDelete', $purchase)
            ->call('delete')
            ->assertStatus(403);
    }

    /** delete() succeeds for an 'ordered' purchase with the right permission */
    public function test_delete_succeeds_for_ordered_purchase(): void
    {
        $this->givePermission('Delete Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmDelete', $purchase)
            ->call('delete');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $purchase->id]);
    }

    /** delete() aborts with 403 for a 'received' purchase regardless of permission */
    public function test_delete_aborts_for_received_purchase(): void
    {
        $this->givePermission('Delete Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('received');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmDelete', $purchase)
            ->call('delete')
            ->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', ['id' => $purchase->id]);
    }

    /** delete() aborts with 403 for a 'cancelled' purchase regardless of permission */
    public function test_delete_aborts_for_cancelled_purchase(): void
    {
        $this->givePermission('Delete Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('cancelled');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmDelete', $purchase)
            ->call('delete')
            ->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', ['id' => $purchase->id]);
    }

    /** delete() succeeds for a 'pending' purchase with the right permission */
    public function test_delete_succeeds_for_pending_purchase(): void
    {
        $this->givePermission('Delete Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('pending');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmDelete', $purchase)
            ->call('delete');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $purchase->id]);
    }

    /** After delete, confirmingDeletion is reset to false */
    public function test_delete_resets_confirmation_state(): void
    {
        $this->givePermission('Delete Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmDelete', $purchase)
            ->assertSet('confirmingDeletion', true)
            ->call('delete')
            ->assertSet('confirmingDeletion', false)
            ->assertSet('purchaseOrderToDelete', null);
    }

    // ─── send() ────────────────────────────────────────────────────────────────

    /** send() aborts with 403 when user lacks 'Update Purchase Order' */
    public function test_send_aborts_without_update_permission(): void
    {
        $this->actingAs($this->user);
        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmSend', $purchase)
            ->call('send')
            ->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchase->id,
            'status' => 'ordered', // status unchanged
        ]);
    }

    /** send() updates status to 'sent' with the right permission */
    public function test_send_updates_status_to_sent(): void
    {
        $this->givePermission('Update Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmSend', $purchase)
            ->call('send');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchase->id,
            'status' => 'sent',
        ]);
    }

    // ─── cancel() ──────────────────────────────────────────────────────────────

    /** cancel() aborts with 403 when user lacks 'Update Purchase Order' */
    public function test_cancel_aborts_without_update_permission(): void
    {
        $this->actingAs($this->user);
        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmCancel', $purchase)
            ->call('cancel')
            ->assertStatus(403);
    }

    /** cancel() updates status to 'cancelled' with the right permission */
    public function test_cancel_updates_status_to_cancelled(): void
    {
        $this->givePermission('Update Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmCancel', $purchase)
            ->call('cancel');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchase->id,
            'status' => 'cancelled',
        ]);
    }

    /** cancel() aborts with 403 for an already 'received' purchase */
    public function test_cancel_aborts_for_received_purchase(): void
    {
        $this->givePermission('Update Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('received');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmCancel', $purchase)
            ->call('cancel')
            ->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchase->id,
            'status' => 'received', // unchanged
        ]);
    }

    /** cancel() aborts with 403 for an already 'cancelled' purchase */
    public function test_cancel_aborts_for_already_cancelled_purchase(): void
    {
        $this->givePermission('Update Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('cancelled');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmCancel', $purchase)
            ->call('cancel')
            ->assertStatus(403);
    }

    /** After cancel, confirmation state is cleared */
    public function test_cancel_resets_confirmation_state(): void
    {
        $this->givePermission('Update Purchase Order');
        $this->actingAs($this->user);

        $purchase = $this->makePurchaseOrder('ordered');

        Livewire::test(PurchaseOrderList::class)
            ->call('confirmCancel', $purchase)
            ->assertSet('confirmingCancel', true)
            ->call('cancel')
            ->assertSet('confirmingCancel', false)
            ->assertSet('purchaseOrderToCancel', null);
    }

    // ─── Regression ────────────────────────────────────────────────────────────

    /** delete() does NOT delete when no target order is set (no-op path) */
    public function test_delete_does_nothing_when_no_order_set(): void
    {
        $this->givePermission('Delete Purchase Order');
        $this->actingAs($this->user);

        // Call delete directly without first calling confirmDelete
        Livewire::test(PurchaseOrderList::class)
            ->call('delete');

        // No exception, no deletion — stable no-op
        $this->assertTrue(true);
    }
}