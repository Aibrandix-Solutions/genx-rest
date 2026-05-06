<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Facades\Excel;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Exports\InventoryItemExport;
use Modules\Inventory\Exports\InventoryItemImportTemplateExport;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\Unit;

class InventoryItemList extends Component
{
    use WithFileUploads;
    use LivewireAlert;

    public $search = '';
    public $showAddInventoryItem = false;
    public $showEditInventoryItemModal = false;
    public $perPage = 20;
    public $importFile;

    #[On('hideAddInventoryItem')]
    public function hideAddInventoryItem()
    {
        $this->showAddInventoryItem = false;
    }

    #[On('hideEditInventoryItemModal')]
    public function hideEditInventoryItemModal()
    {
        $this->showEditInventoryItemModal = false;
    }

    public function export()
    {
        return Excel::download(new InventoryItemExport, 'inventory-items.xlsx');
    }

    public function downloadImportTemplate()
    {
        return Excel::download(new InventoryItemImportTemplateExport(), 'inventory-items-import-template.xlsx');
    }

    public function importItems(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        $rows = Excel::toArray([], $this->importFile)[0] ?? [];
        if (count($rows) < 2) {
            $this->addError('importFile', 'Template appears empty. Please add at least one row.');
            return;
        }

        $headers = array_map(
            fn ($h) => strtolower(trim((string) $h)),
            (array) ($rows[0] ?? [])
        );
        $requiredHeaders = ['name', 'category_name', 'unit_name', 'threshold_quantity', 'unit_purchase_price'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headers, true)) {
                $this->addError('importFile', "Missing required column: {$requiredHeader}");
                return;
            }
        }

        $indexMap = array_flip($headers);
        $restaurantId = restaurant()->id;
        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = (array) $rows[$i];
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $excelRow = $i + 1;
            $name = trim((string) ($row[$indexMap['name']] ?? ''));
            $categoryName = trim((string) ($row[$indexMap['category_name']] ?? ''));
            $unitName = trim((string) ($row[$indexMap['unit_name']] ?? ''));
            $supplierName = trim((string) ($row[$indexMap['preferred_supplier_name']] ?? ''));
            $thresholdRaw = $row[$indexMap['threshold_quantity']] ?? null;
            $priceRaw = $row[$indexMap['unit_purchase_price']] ?? null;

            if ($name === '' || $categoryName === '' || $unitName === '') {
                $errors[] = "Row {$excelRow}: name, category_name and unit_name are required.";
                continue;
            }

            if (!is_numeric($thresholdRaw) || (float) $thresholdRaw < 0) {
                $errors[] = "Row {$excelRow}: threshold_quantity must be a number >= 0.";
                continue;
            }

            if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
                $errors[] = "Row {$excelRow}: unit_purchase_price must be a number >= 0.";
                continue;
            }

            $category = InventoryItemCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first();
            if (!$category) {
                $errors[] = "Row {$excelRow}: category '{$categoryName}' not found.";
                continue;
            }

            $unit = Unit::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($unitName)])->first();
            if (!$unit) {
                $errors[] = "Row {$excelRow}: unit '{$unitName}' not found.";
                continue;
            }

            $supplierId = null;
            if ($supplierName !== '') {
                $supplier = Supplier::query()
                    ->where('restaurant_id', $restaurantId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($supplierName)])
                    ->first();
                if (!$supplier) {
                    $errors[] = "Row {$excelRow}: preferred supplier '{$supplierName}' not found.";
                    continue;
                }
                $supplierId = $supplier->id;
            }

            $existing = InventoryItem::query()
                ->where('restaurant_id', $restaurantId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            $payload = [
                'inventory_item_category_id' => $category->id,
                'unit_id' => $unit->id,
                'threshold_quantity' => (float) $thresholdRaw,
                'unit_purchase_price' => (float) $priceRaw,
                'preferred_supplier_id' => $supplierId,
            ];

            if ($existing) {
                $existing->update($payload);
                $updatedCount++;
            } else {
                InventoryItem::create([
                    ...$payload,
                    'restaurant_id' => $restaurantId,
                    'name' => $name,
                ]);
                $createdCount++;
            }
        }

        $this->importFile = null;

        if (!empty($errors)) {
            $this->addError('importFile', implode(' ', array_slice($errors, 0, 5)));
        }

        if ($createdCount > 0 || $updatedCount > 0) {
            $this->alert('success', "Import complete: {$createdCount} created, {$updatedCount} updated.");
        } elseif (empty($errors)) {
            $this->addError('importFile', 'No rows were imported.');
        }
    }

    public function render()
    {
        return view('inventory::livewire.inventory-item.inventory-item-list');
    }
}
