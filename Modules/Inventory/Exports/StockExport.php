<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\InventoryItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class StockExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    use Exportable;

    private $search;
    private $category;
    private $stockStatus;
    private $locationFilter;

    public function __construct($search, $category, $stockStatus, $locationFilter = null)
    {
        $this->search = $search;
        $this->category = $category;
        $this->stockStatus = $stockStatus;
        $this->locationFilter = $locationFilter;
    }

    public function headings(): array
    {
        return [
            __('inventory::modules.inventoryItem.name'),
            __('inventory::modules.inventoryItem.category'),
            __('inventory::modules.stock.location'),
            __('inventory::modules.stock.currentStock'),
            __('inventory::modules.stock.stockStatus'),
            __('inventory::modules.stock.cost'),
        ];
    }

    public function map($item): array
    {
        $stockStatus = $item->getStockStatus();
        $stock = $item->stocks->first();
        $locationName = $stock && $stock->location ? $stock->location->name : '--';

        return [
            $item->name,
            $item->category->name ?? '--',
            $locationName,
            number_format($item->filtered_stock ?? 0, 2) . ' ' . optional($item->unit)->symbol,
            $stockStatus['status'],
            currency_format($item->total_cost_value ?? 0, restaurant()->currency_id),
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle
            ->getFont()
            ->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill'  => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'f5f5f5'),
            ]],
        ];
    }

    public function collection()
    {
        // Set MySQL to non-strict mode for this query
        DB::statement("SET SESSION sql_mode=''");
 
        $query = InventoryItem::with(['category', 'unit', 'stocks' => function($q) {
                if ($this->locationFilter && $this->locationFilter !== 'all') {
                    $q->where('location_id', $this->locationFilter);
                }
            }, 'stocks.location'])
             ->select('inventory_items.*')
             ->leftJoin('inventory_stocks', function($join) {
                 $join->on('inventory_items.id', '=', 'inventory_stocks.inventory_item_id');
                 
                 // Filter by location if selected
                 if ($this->locationFilter && $this->locationFilter !== 'all') {
                     $join->where('inventory_stocks.location_id', '=', $this->locationFilter);
                 }
             })
             ->selectRaw('COALESCE(SUM(inventory_stocks.quantity), 0) as filtered_stock')
             ->selectRaw('COALESCE(SUM(inventory_stocks.quantity * inventory_items.unit_purchase_price), 0) as total_cost_value')
             ->groupBy('inventory_items.id');
 
         // Apply search filter
         if ($this->search) {
             $query->where('inventory_items.name', 'like', '%' . $this->search . '%');
         }
 
         // Apply category filter
         if ($this->category) {
             $query->where('inventory_items.inventory_item_category_id', $this->category);
         }
 
         // Apply stock status filter
         if ($this->stockStatus) {
             switch ($this->stockStatus) {
                 case 'in_stock':
                     $query->havingRaw('filtered_stock > inventory_items.threshold_quantity');
                     break;
                 case 'low_stock':
                     $query->havingRaw('filtered_stock > 0 AND filtered_stock <= inventory_items.threshold_quantity');
                     break;
                 case 'out_of_stock':
                     $query->havingRaw('filtered_stock <= 0');
                     break;
             }
         }
 
        $results = $query->get();
        
        // Reset SQL mode back to default after query execution
        DB::statement("SET SESSION sql_mode=(SELECT @@global.sql_mode)");
        
        return $results;
    }

}
