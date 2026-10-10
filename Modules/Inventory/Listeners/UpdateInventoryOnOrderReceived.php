<?php

namespace Modules\Inventory\Listeners;

use App\Events\NewOrderCreated;
use Modules\Inventory\Services\InventoryOrderStockService;

class UpdateInventoryOnOrderReceived
{
    public function __construct(private InventoryOrderStockService $stockService) {}

    public function handle(NewOrderCreated $event): void
    {
        $this->stockService->deductForOrder($event->order, $event->orderItemIds);
    }
}
