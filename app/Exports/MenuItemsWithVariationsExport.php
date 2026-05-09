<?php

namespace App\Exports;

use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Scopes\AvailableMenuItemScope;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MenuItemsWithVariationsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected ?int $branchId = null
    ) {
        $this->branchId = $branchId ?? branch()?->id;
    }

    public function sheets(): array
    {
        return [
            new MenuItemsSheet($this->branchId),
            new MenuItemVariationsSheet($this->branchId),
        ];
    }
}

/**
 * Sheet 1: Menu Items
 */
class MenuItemsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected ?int $branchId = null
    ) {}

    public function headings(): array
    {
        return [
            __('modules.menu.itemName'),
            __('modules.menu.itemCode'),
            __('modules.menu.itemType'),
            __('modules.menu.setPrice'),
            __('modules.menu.itemCategory'),
            __('modules.menu.menuName'),
            __('modules.menu.isAvailable'),
        ];
    }

    public function title(): string
    {
        return 'Menu Items (Base Data)';
    }

    public function map($item): array
    {
        return [
            $item->item_name,
            $item->item_code ?? '',
            $item->type,
            (string) round((float) ($item->getAttributes()['price'] ?? 0), 2),
            $item->category->category_name ?? '--',
            $item->menu->menu_name ?? '--',
            $item->is_available ? __('modules.menu.available') : __('modules.menu.notAvailable'),
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
            1 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f5f5f5'],
            ]],
        ];
    }

    public function collection()
    {
        $q = MenuItem::with(['category', 'menu'])
            ->withoutGlobalScope(AvailableMenuItemScope::class);

        if ($this->branchId) {
            $q->where('branch_id', $this->branchId);
        }

        return $q->orderBy('id')->get();
    }
}

/**
 * Sheet 2: Menu Item Variations
 */
class MenuItemVariationsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected ?int $branchId = null
    ) {}

    public function headings(): array
    {
        return [
            'item_code',
            'variation_name',
            'variation_price',
        ];
    }

    public function title(): string
    {
        return 'Item Variations (By Code)';
    }

    public function map($variation): array
    {
        // Get the parent menu item to fetch item_code
        $menuItem = $variation->menuItem;
        
        return [
            $menuItem->item_code ?? '',
            $variation->variation,
            (string) round((float) $variation->price, 2),
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
            1 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f5f5f5'],
            ]],
        ];
    }

    public function collection()
    {
        $q = MenuItemVariation::with('menuItem')
            ->whereHas('menuItem', function ($query) {
                if ($this->branchId) {
                    $query->where('branch_id', $this->branchId);
                }
            });

        return $q->orderBy('menu_item_id')->get();
    }
}
