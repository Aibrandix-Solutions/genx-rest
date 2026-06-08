<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class PurchaseOrderController extends Controller
{
    /**
     * Display the purchases page.
     */
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Purchase Order'), 403);
        
        return view('inventory::purchases.index');
    }

    /**
     * Print-friendly purchases report. Uses the same filters as the purchases
     * list but without pagination so all matching rows print on one page.
     */
    public function reportPrint(Request $request)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Purchase Order'), 403);

        $showAdminView = user_can('View Admin Purchases');
        $search = (string) $request->query('search', '');
        $supplierId = $request->query('supplierId');
        $supplierId = is_numeric($supplierId) ? (int) $supplierId : null;
        $status = (string) $request->query('status', '');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $branchFilter = (string) $request->query('branchFilter', '');

        $statsQuery = PurchaseOrder::query();
        if (!$showAdminView) {
            $statsQuery->where('branch_id', branch()->id);
        } elseif ($branchFilter !== '') {
            $statsQuery->where('branch_id', $branchFilter);
        }

        $stats = [
            'total_orders' => (clone $statsQuery)->count(),
            'pending_orders' => (clone $statsQuery)
                ->whereIn('status', ['ordered', 'pending'])
                ->count(),
            'completed_orders' => (clone $statsQuery)
                ->where('status', 'received')
                ->count(),
        ];

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'payments', 'branch'])
            ->when(!$showAdminView, function ($query) {
                $query->where('branch_id', branch()->id);
            })
            ->when($showAdminView && $branchFilter !== '', function ($query) use ($branchFilter) {
                $query->where('branch_id', $branchFilter);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('po_number', 'like', '%' . $search . '%')
                        ->orWhereHas('supplier', function ($query) use ($search) {
                            $query->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($supplierId, function ($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('order_date', [$startDate, $endDate]);
            })
            ->latest()
            ->get();

        $statuses = [
            'ordered' => trans('inventory::modules.purchaseOrder.status.ordered'),
            'pending' => trans('inventory::modules.purchaseOrder.status.pending'),
            'received' => trans('inventory::modules.purchaseOrder.status.received'),
            'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
        ];

        $branchName = null;
        if ($showAdminView) {
            $branchName = $branchFilter !== ''
                ? Branch::where('restaurant_id', restaurant()->id)->find($branchFilter)?->name
                : trans('app.all');
        } else {
            $branchName = branch()->name;
        }

        $supplierName = $supplierId
            ? Supplier::where('restaurant_id', restaurant()->id)->find($supplierId)?->name
            : trans('inventory::modules.purchaseOrder.all_suppliers');

        return view('inventory::purchases.report-print', [
            'purchaseOrders' => $purchaseOrders,
            'stats' => $stats,
            'statuses' => $statuses,
            'search' => $search,
            'supplierName' => $supplierName,
            'statusFilter' => $status !== '' ? ($statuses[$status] ?? ucfirst($status)) : trans('inventory::modules.purchaseOrder.all_status'),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchName' => $branchName,
            'showAdminView' => $showAdminView,
        ]);
    }

    public function create()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Create Purchase Order'), 403);

        return view('inventory::purchases.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $po = PurchaseOrder::create([
                'branch_id' => auth()->user()->branch_id,
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
                'status' => 'draft',
            ]);

            $po->generatePoNumber();
            $po->save();

            foreach ($validated['items'] as $item) {
                $po->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $po->update(['total_amount' => $po->items->sum('subtotal')]);
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order created successfully.');
    }

    /**
     * Generate PDF for the purchase
     */
    public function generatePdf(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->branch_id !== auth()->user()->branch_id, 403);

        $pdf = PDF::loadView('inventory::purchase-orders.pdf', [
            'purchaseOrder' => $purchaseOrder->load(['supplier', 'location.branch', 'items.inventoryItem.unit', 'creator', 'branch.restaurant'])
        ]);

        $pdf->getDomPDF()->set_option('defaultFont', 'Arial');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);

        return $pdf->download("PURCHASE-{$purchaseOrder->po_number}.pdf");
    }

    public function edit(Request $request, PurchaseOrder $purchase)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!(user_can('Update Purchase Order') || user_can('Edit Purchase Order')), 403);
        abort_if($purchase->branch_id !== branch()->id, 403);

        // Received purchases require special override permission
        if ($purchase->status === 'received') {
            abort_if(!user_can('Edit Received Purchase'), 403);
        }

        return view('inventory::purchases.edit', [
            'purchase' => $purchase,
            'returnTo' => $request->query('return'),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchase)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Edit Purchase Order'), 403);

        return redirect()->route('purchases.index');
    }

    // ... Add other controller methods (show, edit, update, destroy) ...
} 