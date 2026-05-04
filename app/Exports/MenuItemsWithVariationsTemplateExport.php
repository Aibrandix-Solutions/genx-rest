<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MenuItemsWithVariationsTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new MenuItemsTemplateSheet(),
            new MenuItemVariationsTemplateSheet(),
        ];
    }
}

class MenuItemsTemplateSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'item_name',
            'item_code',
            'category_name',
            'menu_name',
            'price',
            'type',
            'show_on_customer_site',
            'description',
        ];
    }

    public function title(): string
    {
        return 'Menu Items (Template)';
    }

    public function array(): array
    {
        return [
            ['Chicken Burger', 'IT1001', 'Burgers', 'Main Menu', 12.5, 'non-veg', 'yes', 'Crispy chicken burger'],
            ['Veg Burger', '', 'Burgers', 'Main Menu', 10, 'veg', 'yes', 'Classic veg burger'],
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle->getFont()->setName('Arial');
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
}

class MenuItemVariationsTemplateSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
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
        return 'Item Variations (Template)';
    }

    public function array(): array
    {
        return [
            ['IT1001', 'Small', 10],
            ['IT1001', 'Large', 14],
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle->getFont()->setName('Arial');
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
}