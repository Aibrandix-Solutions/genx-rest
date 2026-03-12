<?php

namespace Modules\Inventory\Livewire\StockTransfer;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryItem;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockTransferList extends Component
{
    use WithPagination;

    public $filterType = 'all'; // all, outgoing, incoming
    public $statusFilter = 'all'; // all, pending, in_transit, completed, cancelled
    public $search = '';
    public $startDate = null;
    public $endDate = null;
    public $perPage = 20;
    public $showAdminView = false;
    public $branchFilter = '';
    public $selectedTransfer = null;
    public $showViewModal = false;
    public $showReceiveModal = false;
    public $receiveModalKey = 0;
    public $showModal = false;
    public $showEditModal = false;
    public $editTransferId = null;
    public $confirmingInitiation = false;
    public $selectedTransferForInitiation = null;
    public $confirmingCancellation = false;
    public $selectedTransferForCancellation = null;

    protected $queryString = [
        'filterType' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        $this->filterType = 'all';
        $this->showAdminView = user_can('View Admin Transfers');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function viewTransfer($transferId)
    {
        abort_if(!user_can('Show Stock Transfer'), 403);

        $this->selectedTransfer = InventoryTransfer::with([
            'sourceBranch',
            'destinationBranch',
            'sourceLocation',
            'destinationLocation',
            'createdBy',
            'confirmedBy',
            'items.sourceItem.unit',
            'items.destinationItem.unit',
        ])->findOrFail($transferId);
        
        $this->showViewModal = true;
    }

    public function confirmInitiate($transferId)
    {
        $this->selectedTransferForInitiation = $transferId;
        $this->confirmingInitiation = true;
    }

    public function confirmCancel($transferId)
    {
        $this->selectedTransferForCancellation = $transferId;
        $this->confirmingCancellation = true;
    }

    public function cancelTransfer($transferId)
    {
        abort_if(!user_can('Cancel Stock Transfer'), 403);

        try {
            DB::transaction(function () use ($transferId) {
                $transfer = InventoryTransfer::with('items')->findOrFail($transferId);
                
                if (!$transfer->canBeCancelled()) {
                    throw new \Exception(__('inventory::modules.transfers.cannot_cancel_transfer'));
                }

                // Restaurant-scoped: any user can cancel transfers within the restaurant

                // If transfer is in_transit, handle stock restoration based on partial receives
                if ($transfer->status === 'in_transit') {
                    foreach ($transfer->items as $item) {
                        $confirmedQty = $item->confirmed_quantity ?? 0;
                        $requestedQty = $item->requested_quantity;
                        
                        // If items were partially/fully received
                        if ($confirmedQty > 0) {
                            // Deduct confirmed quantity from destination branch (reverse the receive)
                            $destinationStock = InventoryStock::where('inventory_item_id', $item->destination_inventory_item_id)
                                ->where('branch_id', $transfer->destination_branch_id)
                                ->first();

                            if ($destinationStock && $destinationStock->quantity >= $confirmedQty) {
                                $destinationStock->quantity -= $confirmedQty;
                                $destinationStock->save();

                                // Get destination item price for reversal movement
                                // Use the price that was recorded when item was received
                                $destinationItem = InventoryItem::query()->find($item->destination_inventory_item_id);
                                $destinationUnitPrice = $destinationItem ? ($destinationItem->unit_purchase_price ?? 0) : 0;
                                
                                // Try to get the price from the original destination movement
                                $originalMovement = InventoryMovement::where('inventory_transfer_id', $transfer->id)
                                    ->where('inventory_transfer_item_id', $item->id)
                                    ->where('transaction_type', 'in')
                                    ->where('branch_id', $transfer->destination_branch_id)
                                    ->first();
                                
                                $unitPriceForReversal = $originalMovement && $originalMovement->unit_purchase_price 
                                    ? $originalMovement->unit_purchase_price 
                                    : $destinationUnitPrice;
                                
                                // Create reversal movement for destination (out)
                                InventoryMovement::create([
                                    'branch_id' => $transfer->destination_branch_id,
                                    'inventory_item_id' => $item->destination_inventory_item_id,
                                    'quantity' => $confirmedQty,
                                    'transaction_type' => 'out',
                                    'transfer_branch_id' => null,
                                    'inventory_transfer_id' => $transfer->id,
                                    'inventory_transfer_item_id' => $item->id,
                                    'unit_purchase_price' => $unitPriceForReversal,
                                    'added_by' => user()->id,
                                ]);
                            }
                        }

                        // Restore stock to source location
                        // If partial receive: restore only the NOT received quantity
                        // If no receive: restore full requested quantity
                        $quantityToRestore = $requestedQty - $confirmedQty;
                        
                        if ($quantityToRestore > 0) {
                            if ($transfer->source_location_id) {
                                $sourceStock = InventoryStock::where('inventory_item_id', $item->source_inventory_item_id)
                                    ->where('location_id', $transfer->source_location_id)
                                    ->first();
                            } else {
                                // Fallback to branch-based lookup for old transfers
                                $sourceStock = InventoryStock::where('inventory_item_id', $item->source_inventory_item_id)
                                    ->where('branch_id', $transfer->source_branch_id)
                                    ->first();
                            }

                            if ($sourceStock) {
                                // Restore the not-yet-received quantity
                                $sourceStock->quantity += $quantityToRestore;
                                $sourceStock->save();

                                // Create reversal movement for source (only for quantity not received)
                                if ($quantityToRestore > 0) {
                                    InventoryMovement::create([
                                        'branch_id' => $transfer->source_branch_id,
                                        'inventory_item_id' => $item->source_inventory_item_id,
                                        'quantity' => $quantityToRestore,
                                        'transaction_type' => 'in',
                                        'transfer_branch_id' => null,
                                        'inventory_transfer_id' => $transfer->id,
                                        'inventory_transfer_item_id' => $item->id,
                                        'added_by' => user()->id,
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Update all items to cancelled status
                foreach ($transfer->items as $item) {
                    $item->status = 'cancelled';
                    $item->save();
                }

                // Update transfer status
                $transfer->status = 'cancelled';
                $transfer->save();
            });

            session()->flash('success', __('inventory::modules.transfers.transfer_cancelled_successfully'));
            $this->dispatch('transferCancelled');
            $this->confirmingCancellation = false;
            $this->selectedTransferForCancellation = null;
            $this->resetPage();
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->confirmingCancellation = false;
            $this->selectedTransferForCancellation = null;
        }
    }

    public function initiateTransfer($transferId)
    {
        abort_if(!user_can('Update Stock Transfer'), 403);

        try {
            DB::transaction(function () use ($transferId) {
                $transfer = InventoryTransfer::with('items', 'sourceLocation')->findOrFail($transferId);
                
                if ($transfer->status !== 'pending') {
                    throw new \Exception(__('inventory::modules.transfers.cannot_initiate_transfer'));
                }

                // Restaurant-scoped: any user can initiate transfers

                foreach ($transfer->items as $item) {
                    // Check stock availability at source location
                    if ($transfer->source_location_id) {
                        $stock = InventoryStock::where('inventory_item_id', $item->source_inventory_item_id)
                            ->where('location_id', $transfer->source_location_id)
                            ->first();
                    } else {
                        // Fallback to branch-based lookup for old transfers
                        $stock = InventoryStock::where('inventory_item_id', $item->source_inventory_item_id)
                            ->where('branch_id', branch()->id)
                            ->first();
                    }
                    
                    if (!$stock || $stock->quantity < $item->requested_quantity) {
                        $itemName = $item->sourceItem ? $item->sourceItem->name : __('inventory::modules.transfers.item');
                        throw new \Exception(__('inventory::modules.transfers.insufficient_stock_for_item', [
                            'item' => $itemName
                        ]));
                    }

                    // Deduct from source stock
                    $stock->quantity -= $item->requested_quantity;
                    $stock->save();

                    // Check if source movement already exists (for individual transfers)
                    $sourceMovement = InventoryMovement::where('inventory_transfer_id', $transfer->id)
                        ->where('inventory_transfer_item_id', $item->id)
                        ->where('transaction_type', 'transfer')
                        ->where('branch_id', branch()->id)
                        ->first();

                    // Get source item to retrieve unit purchase price
                    $sourceItem = InventoryItem::query()->find($item->source_inventory_item_id);
                    $sourceUnitPrice = $sourceItem ? ($sourceItem->unit_purchase_price ?? 0) : 0;

                    if ($sourceMovement) {
                        // Update existing movement (individual transfer from AddStockEntry)
                        // Ensure unit price is set if not already
                        if (!$sourceMovement->unit_purchase_price) {
                            $sourceMovement->unit_purchase_price = $sourceUnitPrice;
                        }
                        $sourceMovement->save(); // Already linked, just ensure it's saved
                    } else {
                        // Create new source movement (bulk transfer)
                        InventoryMovement::create([
                            'branch_id' => branch()->id,
                            'inventory_item_id' => $item->source_inventory_item_id,
                            'quantity' => $item->requested_quantity,
                            'transaction_type' => 'transfer',
                            'transfer_branch_id' => $transfer->destination_branch_id,
                            'inventory_transfer_id' => $transfer->id,
                            'inventory_transfer_item_id' => $item->id,
                            'unit_purchase_price' => $sourceUnitPrice,
                            'added_by' => user()->id,
                        ]);
                    }

                    // Check if destination movement already exists
                    $destinationMovement = InventoryMovement::where('inventory_transfer_id', $transfer->id)
                        ->where('inventory_transfer_item_id', $item->id)
                        ->where('transaction_type', 'in')
                        ->where('branch_id', $transfer->destination_branch_id)
                        ->first();

                    if (!$destinationMovement) {
                        // Get destination item to retrieve unit purchase price
                        // Use destination item's price for destination branch records
                        $destinationItem = InventoryItem::query()->find($item->destination_inventory_item_id);
                        $destinationUnitPrice = $destinationItem ? ($destinationItem->unit_purchase_price ?? 0) : 0;
                        
                        // Also get source item price for reference (use source price for cost tracking)
                        $sourceItem = InventoryItem::query()->find($item->source_inventory_item_id);
                        $sourceUnitPrice = $sourceItem ? ($sourceItem->unit_purchase_price ?? 0) : 0;
                        
                        // Use source price for destination movement (maintains cost basis from source)
                        // This is the price at which the item was transferred from source
                        $unitPriceForDestination = $sourceUnitPrice;
                        
                        // Create destination movement
                        InventoryMovement::withoutEvents(function () use ($transfer, $item, $unitPriceForDestination) {
                            $movement = new InventoryMovement();
                            $movement->branch_id = $transfer->destination_branch_id;
                            $movement->inventory_item_id = $item->destination_inventory_item_id;
                            $movement->quantity = $item->requested_quantity;
                            $movement->transaction_type = 'in';
                            $movement->transfer_branch_id = branch()->id;
                            $movement->inventory_transfer_id = $transfer->id;
                            $movement->inventory_transfer_item_id = $item->id;
                            $movement->unit_purchase_price = $unitPriceForDestination;
                            $movement->added_by = user()->id;
                            $movement->save();
                            return $movement;
                        });
                    }

                    // Update item status
                    $item->status = 'in_transit';
                    $item->save();
                }

                $transfer->status = 'in_transit';
                $transfer->save();
            });

            session()->flash('success', __('inventory::modules.transfers.transfer_initiated_successfully'));
            $this->dispatch('transferInitiated');
            $this->confirmingInitiation = false;
            $this->selectedTransferForInitiation = null;
            $this->resetPage();
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->confirmingInitiation = false;
            $this->selectedTransferForInitiation = null;
        }
    }

    public function openReceiveModal($transferId)
    {
        abort_if(!user_can('Update Stock Transfer'), 403);

        $transfer = InventoryTransfer::with([
            'items.sourceItem.unit',
            'items.destinationItem.unit',
        ])->findOrFail($transferId);

        $this->selectedTransfer = $transfer;
        $this->receiveModalKey++;
        $this->showReceiveModal = true;
    }

    public function updatedShowReceiveModal($value)
    {
        if (!$value) {
            $this->selectedTransfer = null;
        }
    }

    public function closeModals()
    {
        $this->showViewModal = false;
        $this->showReceiveModal = false;
        $this->showModal = false;
        $this->selectedTransfer = null;
    }

    protected $listeners = [
        'transferCreated'         => '$refresh',
        'transferInitiated'       => '$refresh',
        'transferReceived'        => '$refresh',
        'transferCancelled'       => '$refresh',
        'transferUpdated'         => 'handleTransferUpdated',
        'closeCreateTransferModal' => 'closeCreateTransferModal',
        'closeReceiveModal'       => 'closeReceiveModal',
        'closeEditTransferModal'  => 'closeEditTransferModal',
        'closeModal'              => 'closeCreateTransferModal',
    ];

    public function closeCreateTransferModal()
    {
        $this->showModal = false;
        $this->resetPage();
    }

    public function openEditModal($transferId)
    {
        abort_if(!user_can('Update Stock Transfer'), 403);

        $this->editTransferId = $transferId;
        $this->showEditModal  = true;
    }

    public function closeEditTransferModal()
    {
        $this->showEditModal  = false;
        $this->editTransferId = null;
    }

    public function handleTransferUpdated()
    {
        $this->closeEditTransferModal();
        $this->resetPage();
    }

    public function closeReceiveModal()
    {
        $this->showReceiveModal = false;
        $this->selectedTransfer = null;
    }

    private function getTransfersQuery()
    {
        $query = InventoryTransfer::with([
            'sourceBranch',
            'destinationBranch',
            'sourceLocation',
            'destinationLocation',
            'createdBy',
            'items'
        ])->where('restaurant_id', restaurant()->id);

        // Show all transfers in restaurant (restaurant-scoped)
        // No branch filtering - all users can see and manage all transfers

        // Filter by status
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Filter by direction relative to the current branch
        if ($this->filterType === 'outgoing') {
            $query->where(function ($q) {
                $q->where('source_branch_id', branch()->id)
                  ->orWhereHas('sourceLocation', fn ($sq) => $sq->where('branch_id', branch()->id));
            });
        } elseif ($this->filterType === 'incoming') {
            $query->where(function ($q) {
                $q->where('destination_branch_id', branch()->id)
                  ->orWhereHas('destinationLocation', fn ($sq) => $sq->where('branch_id', branch()->id));
            });
        }

        // Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('transfer_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('sourceBranch', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('destinationBranch', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterType', 'statusFilter', 'startDate', 'endDate']);
        $this->resetPage();
    }

    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \Modules\Inventory\Exports\StockTransferExport($this->search, $this->startDate, $this->endDate, $this->filterType, $this->statusFilter), 'stock-transfers.xlsx');
    }

    public function render()
    {
        $transfers = $this->getTransfersQuery()->paginate($this->perPage);
        $branches = $this->showAdminView ? \App\Models\Branch::where('restaurant_id', restaurant()->id)->orderBy('name')->get() : [];

        return view('inventory::livewire.stock-transfer.stock-transfer-list', [
            'transfers' => $transfers,
            'branches' => $branches,
            'showAdminView' => $this->showAdminView,
        ]);
    }
}

