<?php

namespace App\Imports;

use App\Models\MenuItem;
use App\Models\ItemCategory;
use App\Models\Menu;
use App\Scopes\AvailableMenuItemScope;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Log;

class MenuItemImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $restaurantId;
    protected $branchId;
    protected $kitchenId;
    protected $columnMapping = [];
    protected $results = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'categories_created' => 0,
        'menus_created' => 0
    ];
    protected $errors = [];

    /** @var list<string> */
    private array $pendingAutoItemCodes = [];

    private ?int $autoCodeSequence = null;

    public function __construct($restaurantId, $branchId, $kitchenId = null, $columnMapping = [])
    {
        $this->restaurantId = $restaurantId;
        $this->branchId = $branchId;
        $this->kitchenId = $kitchenId;
        $this->columnMapping = $columnMapping;
    }

    public function model(array $row)
    {
        $this->results['total']++;

        try {
            // Map the row data using column mapping
            $mappedRow = $this->mapRowData($row);

            // Find category by name (using JSON query for translatable field)
            $category = ItemCategory::where('branch_id', $this->branchId)
                ->whereRaw("JSON_EXTRACT(category_name, '$.en') = ?", [$mappedRow['category_name'] ?? ''])
                ->first();


            if (!$category) {
                // Auto-create the category if it doesn't exist
                $category = ItemCategory::create([
                    'category_name' => ['en' => $mappedRow['category_name']],
                    'branch_id' => $this->branchId,
                    'is_active' => true,
                ]);
                $this->results['categories_created']++;
                Log::info("Auto-created category: {$mappedRow['category_name']} for branch {$this->branchId}");
            }

            // Find menu by name (using JSON query for translatable field)
            $menu = Menu::where('branch_id', $this->branchId)
                ->whereRaw("JSON_EXTRACT(menu_name, '$.en') = ?", [$mappedRow['menu_name'] ?? ''])
                ->first();


            if (!$menu) {
                // Auto-create the menu if it doesn't exist
                $menu = Menu::create([
                    'menu_name' => ['en' => $mappedRow['menu_name']],
                    'branch_id' => $this->branchId,
                    'is_active' => true,
                ]);
                $this->results['menus_created']++;
                Log::info("Auto-created menu: {$mappedRow['menu_name']} for branch {$this->branchId}");
            }

            $itemCodeRaw = isset($mappedRow['item_code']) ? trim((string) $mappedRow['item_code']) : '';
            $itemCode = $itemCodeRaw !== '' ? $itemCodeRaw : null;

            if ($itemCode !== null) {
                $existingByCode = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
                    ->where('branch_id', $this->branchId)
                    ->where('item_code', $itemCode)
                    ->first();
                if ($existingByCode) {
                    Log::info('Menu item skipped: duplicate item_code in branch', ['item_code' => $itemCode]);
                    $this->results['skipped']++;

                    return null;
                }
            }

            // Check for duplicate menu item by name and category
            $existingMenuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
                ->where('branch_id', $this->branchId)
                ->where('item_name', $mappedRow['item_name'] ?? '')
                ->where('item_category_id', $category->id)
                ->first();

            if ($existingMenuItem) {
                Log::info('Menu item already exists: '.($mappedRow['item_name'] ?? ''));
                $this->results['skipped']++;
                return null;
            }

            if ($itemCode === null) {
                $itemCode = $this->allocateAutoItemCodeForImport();
            }

            // Prepare the data
            $data = [
                'item_name' => $mappedRow['item_name'] ?? '',
                'item_code' => $itemCode,
                'description' => $mappedRow['description'] ?? '',
                'price' => floatval($mappedRow['price'] ?? 0),
                'item_category_id' => $category->id,
                'menu_id' => $menu->id,
                'type' => $this->mapItemType($mappedRow['type'] ?? 'veg'),
                'is_available' => 1, // Default to available (1 = yes, 0 = no)
                'show_on_customer_site' => $this->mapBoolean($mappedRow['show_on_customer_site'] ?? 'yes'),
                'branch_id' => $this->branchId,
                'kot_place_id' => $this->kitchenId,
            ];

            $this->results['success']++;
            return new MenuItem($data);
        } catch (\Exception $e) {
            Log::error("Error importing menu item: " . $e->getMessage(), ['row' => $row]);
            $this->results['failed']++;
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'item_name' => 'required|string|max:255',
            'item_code' => 'nullable|string|max:50',
            'category_name' => 'required|string|max:255',
            'menu_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'nullable|string|in:veg,non-veg,egg',
            'show_on_customer_site' => 'nullable|string|in:yes,no,1,0,true,false',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 100;
    }

    private function mapItemType($type)
    {
        $type = strtolower(trim($type));

        switch ($type) {
            case 'non-veg':
            case 'nonveg':
            case 'non_veg':
            case 'non veg':
                return MenuItem::NONVEG;
            case 'egg':
                return MenuItem::EGG;
            case 'veg':
            case 'vegetarian':
            default:
                return MenuItem::VEG;
        }
    }

    private function mapBoolean($value)
    {
        $value = strtolower(trim($value));

        return in_array($value, ['yes', '1', 'true', 'y']) ? 1 : 0;
    }

    /**
     * Allocate sequential IT#### codes during import, including across batched inserts
     * (rows not yet flushed are tracked in memory).
     */
    private function allocateAutoItemCodeForImport(): string
    {
        if ($this->autoCodeSequence === null) {
            $this->autoCodeSequence = MenuItem::maxAutoGeneratedItemCodeSuffixForBranch($this->branchId);
        }

        do {
            $this->autoCodeSequence++;
            $candidate = MenuItem::formatAutoItemCode($this->autoCodeSequence);
            $inDb = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
                ->where('branch_id', $this->branchId)
                ->where('item_code', $candidate)
                ->exists();
            $pending = in_array($candidate, $this->pendingAutoItemCodes, true);
        } while ($inDb || $pending);

        $this->pendingAutoItemCodes[] = $candidate;

        return $candidate;
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

        // If no column mapping is provided, return the row as-is
        if (empty($this->columnMapping)) {
            return $row;
        }

        // Map the row data using the column mapping
        foreach ($this->columnMapping as $csvHeader => $mappedField) {
            if (!empty($mappedField) && isset($row[$csvHeader])) {
                // Clean the data and ensure proper encoding
                $value = $row[$csvHeader];
                if (is_string($value)) {
                    // Remove BOM if present and trim whitespace
                    $value = trim($value, "\xEF\xBB\xBF");
                    $value = trim($value);
                }
                $mappedRow[$mappedField] = $value;
            }
        }

        return $mappedRow;
    }
}
