<?php

namespace App\Exports;

use App\Models\MenuItem;
use App\Scopes\AvailableMenuItemScope;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MenuItemExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        protected ?int $branchId = null
    ) {
        $this->branchId = $branchId ?? branch()?->id;
    }

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
