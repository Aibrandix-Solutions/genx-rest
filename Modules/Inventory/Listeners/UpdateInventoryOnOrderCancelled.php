<?php

namespace Modules\Inventory\Listeners;

use App\Events\OrderCancelled;
use Modules\Inventory\Services\InventoryOrderStockService;

class UpdateInventoryOnOrderCancelled
{
    public function __construct(private InventoryOrderStockService $stockService) {}

    public function handle(OrderCancelled $event): void
    {
        $this->stockService->restoreForOrder($event->order);
    }
}
