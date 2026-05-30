<?php

namespace App\Imports;

use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Scopes\AvailableMenuItemScope;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MenuItemVariationImport implements WithMultipleSheets
{
    protected MenuItemVariationSheetImport $sheetImport;

    public function __construct($branchId, $columnMapping = [], $mergeMode = true, $itemsToReplace = [])
    {
        $this->sheetImport = new MenuItemVariationSheetImport($branchId, $columnMapping, $mergeMode, $itemsToReplace);
    }

    public function sheets(): array
    {
        return [
            1 => $this->sheetImport,
        ];
    }

    public function performCleanup(): void
    {
        $this->sheetImport->performCleanup();
    }

    public function getResults(): array
    {
        return $this->sheetImport->getResults();
    }

    public function getErrors(): array
    {
        return $this->sheetImport->getErrors();
    }
}

class MenuItemVariationSheetImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsOnError, SkipsOnFailure, WithBatchInserts
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $branchId;
    protected $columnMapping = [];
    protected $mergeMode = true;
    protected $itemsToReplace = [];
    protected $results = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'deleted' => 0,
    ];
    protected $errors = [];
    protected $importedVariationNamesByItemId = [];

    public function __construct($branchId, $columnMapping = [], $mergeMode = true, $itemsToReplace = [])
    {
        $this->branchId = $branchId;
        $this->columnMapping = $columnMapping;
        $this->mergeMode = $mergeMode;
        $this->itemsToReplace = $itemsToReplace;
    }

    public function model(array $row)
    {
        $this->results['total']++;

        try {
            $mappedRow = $this->mapRowData($row);

            $itemCode = isset($mappedRow['item_code']) ? trim((string) $mappedRow['item_code']) : '';
            if (empty($itemCode)) {
                Log::warning('Menu item variation skipped: missing item_code', ['row' => $row]);
                $this->results['skipped']++;
                return null;
            }

            $menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
                ->where('branch_id', $this->branchId)
                ->where('item_code', $itemCode)
                ->first();

            if (!$menuItem) {
                Log::warning('Menu item variation skipped: orphan item_code', ['item_code' => $itemCode]);
                $this->results['skipped']++;
                return null;
            }

            if (!$this->mergeMode && !in_array($menuItem->id, $this->itemsToReplace, true)) {
                $this->itemsToReplace[] = $menuItem->id;
            }

            $variationName = isset($mappedRow['variation_name']) ? trim((string) $mappedRow['variation_name']) : '';
            if (empty($variationName)) {
                Log::warning('Menu item variation skipped: missing variation_name', ['item_code' => $itemCode]);
                $this->results['skipped']++;
                return null;
            }

            $variationPrice = floatval($mappedRow['variation_price'] ?? 0);
            if ($variationPrice < 0) {
                Log::warning('Menu item variation skipped: negative price', ['item_code' => $itemCode, 'variation_name' => $variationName]);
                $this->results['skipped']++;
                return null;
            }

            $this->importedVariationNamesByItemId[$menuItem->id][$variationName] = true;

            $existing = MenuItemVariation::where('menu_item_id', $menuItem->id)
                ->where('variation', $variationName)
                ->first();

            if ($existing) {
                $existing->update(['price' => $variationPrice]);
                $this->results['success']++;
                return null;
            }

            $this->results['success']++;
            return new MenuItemVariation([
                'menu_item_id' => $menuItem->id,
                'variation' => $variationName,
                'price' => $variationPrice,
            ]);
        } catch (\Exception $e) {
            Log::error('Error importing menu item variation: ' . $e->getMessage(), ['row' => $row]);
            $this->results['failed']++;
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function performCleanup(): void
    {
        if ($this->mergeMode) {
            return;
        }

        $this->cleanupReplacedVariations($this->importedVariationNamesByItemId);
    }

    /**
     * Delete variations for replaced items that are not present in the uploaded file.
     * The upload identifies variations by item_code + variation_name, so we delete
     * by menu item and variation name rather than by a nonexistent variation ID.
     */
    protected function cleanupReplacedVariations(array $importedVariationNamesByItemId): void
    {
        foreach ($this->itemsToReplace as $menuItemId) {
            $importedVariationNames = array_keys($importedVariationNamesByItemId[$menuItemId] ?? []);

            $query = MenuItemVariation::where('menu_item_id', $menuItemId);
            if (!empty($importedVariationNames)) {
                $query->whereNotIn('variation', $importedVariationNames);
            }

            $this->results['deleted'] += $query->delete();
        }
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function mapRowData(array $row)
    {
        $mappedRow = [];

        if (empty($this->columnMapping)) {
            return $row;
        }

        foreach ($this->columnMapping as $csvHeader => $mappedField) {
            if (!empty($mappedField) && isset($row[$csvHeader])) {
                $value = $row[$csvHeader];
                if (is_string($value)) {
                    $value = trim($value, "\xEF\xBB\xBF");
                    $value = trim($value);
                }
                $mappedRow[$mappedField] = $value;
            }
        }

        return $mappedRow;
    }
}
