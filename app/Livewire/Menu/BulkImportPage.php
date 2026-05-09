<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\MenuItem;
use App\Models\ItemCategory;
use Livewire\WithFileUploads;
use App\Imports\MenuItemImport;
use App\Imports\MenuItemVariationImport;
use App\Exports\MenuItemsWithVariationsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BulkImportPage extends Component
{
    use WithFileUploads, LivewireAlert;

    // File upload properties
    public $uploadFile;
    public $uploadProgress = 0;
    public $uploadStatus = '';
    public $uploadErrors = [];
    public $uploadSuccess = false;
    public $uploadStage = 'idle'; // idle, validating, processing, completed, failed
    public $uploadResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'categories_created' => 0,
        'menus_created' => 0
    ];
    public $currentStage = '';
    public $stageProgress = 0;
    public $importProgress = 0;
    public $currentRow = 0;
    public $totalRowsToProcess = 0;
    public $isImporting = false;

    // Available data for reference
    public $availableCategories = [];
    public $availableMenus = [];
    public $availableKitchens;
    public $selectedKitchenId = null;

    // CSV Preview properties
    public $csvData = [];
    public $csvHeaders = [];
    public $columnMapping = [];
    public $previewRows = [];
    public $totalRows = 0;
    
    // Import mode properties
    public $importMode = 'merge'; // 'merge' or 'replace' - controls variation handling
    public $variationsSheetHeaders = [];
    public $variationsPreviewRows = [];
    public $variationsTotalRows = 0;

    public function mount()
    {
        // Initialize with empty values to prevent mount errors
        $this->availableCategories = [];
        $this->availableMenus = [];
        $this->availableKitchens = collect();
        $this->loadAvailableData();

        // Clean up any old temporary files
        $this->cleanupOldTempFiles();
    }

    public function loadAvailableData()
    {
        try {
            $branch = branch();
            if (!$branch || !$branch->id) {
                $this->availableCategories = [];
                $this->availableMenus = [];
                $this->availableKitchens = collect();
                return;
            }

            $this->availableCategories = ItemCategory::where('branch_id', $branch->id)->get()->pluck('category_name')->toArray();
            $this->availableMenus = Menu::where('branch_id', $branch->id)->get()->pluck('menu_name')->toArray();
            $this->availableKitchens = \App\Models\KotPlace::where('branch_id', $branch->id)->where('is_active', true)->get();

            // Auto-select kitchen if only one exists
            if ($this->availableKitchens->count() === 1) {
                $this->selectedKitchenId = $this->availableKitchens->first()->id;
            }
        } catch (\Exception $e) {
            $this->availableCategories = [];
            $this->availableMenus = [];
            $this->availableKitchens = collect();
        }
    }

    public function resetUploadState()
    {
        $this->uploadFile = null;
        $this->uploadProgress = 0;
        $this->uploadStatus = '';
        $this->uploadErrors = [];
        $this->uploadSuccess = false;
        $this->uploadStage = 'idle';
        $this->uploadResults = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'categories_created' => 0,
            'menus_created' => 0
        ];
        $this->currentStage = '';
        $this->stageProgress = 0;
        $this->selectedKitchenId = null;

        // Reset CSV preview data
        $this->csvData = [];
        $this->csvHeaders = [];
        $this->columnMapping = [];
        $this->previewRows = [];
        $this->totalRows = 0;
        $this->variationsSheetHeaders = [];
        $this->variationsPreviewRows = [];
        $this->variationsTotalRows = 0;
        $this->importMode = 'merge';

        $this->loadAvailableData();
    }

    public function goToPreview()
    {
        if (!$this->uploadFile || ($this->availableKitchens->count() > 1 && !$this->selectedKitchenId)) {
            $this->alert('error', __('app.pleaseCompleteAllSteps'));
            return;
        }

        try {
            $this->uploadStage = 'preview';
            $this->detectFileTypeAndParse();
        } catch (\Exception $e) {
            $this->alert('error', __('app.errorParsingFile') . ': ' . $e->getMessage());
            $this->uploadStage = 'idle';
        }
    }

    /**
     * Detect whether file is CSV or Excel and parse accordingly
     */
    private function detectFileTypeAndParse()
    {
        $filePath = $this->uploadFile->getRealPath();
        $extension = strtolower($this->uploadFile->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'])) {
            $this->parseExcelFile($filePath);
        } else {
            $this->parseCsvFile($filePath);
        }
    }

    /**
     * Parse Excel file with up to 2 sheets.
     * Sheet 0 is treated as the item sheet and sheet 1, if present, as variations.
     */
    private function parseExcelFile($filePath)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

            // Always use the first sheet for items so descriptive sheet titles still work.
            $itemsSheet = $spreadsheet->getSheetCount() > 0 ? $spreadsheet->getSheet(0) : null;
            if ($itemsSheet) {
                $this->parseExcelSheet($itemsSheet, true);
            }
            
            // The second sheet is optional and treated as variations.
            if ($spreadsheet->getSheetCount() > 1) {
                $variationsSheet = $spreadsheet->getSheet(1);
                if ($variationsSheet) {
                    $this->parseExcelSheet($variationsSheet, false);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Parse a single Excel sheet
     */
    private function parseExcelSheet($sheet, $isItemsSheet)
    {
        $rows = $sheet->toArray();
        
        if (empty($rows)) {
            throw new \Exception('Empty sheet detected');
        }

        // First row is headers
        $headers = array_map(function ($header) {
            return trim($header ?? '', "\xEF\xBB\xBF");
        }, $rows[0]);

        // Data rows start from index 1
        $dataRows = array_slice($rows, 1);

        if ($isItemsSheet) {
            $this->csvHeaders = $headers;
            $this->previewRows = $dataRows;
            $this->totalRows = count($rows);
            $this->initializeColumnMapping();
        } else {
            $this->variationsSheetHeaders = $headers;
            $this->variationsPreviewRows = $dataRows;
            $this->variationsTotalRows = count($rows);
            $this->initializeVariationsColumnMapping();
        }
    }

    private function parseCsvFile($filePath)
    {
        // Try to detect the file encoding
        $content = file_get_contents($filePath);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        // If not UTF-8, convert it
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            file_put_contents($filePath, $content);
        }

        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \Exception('Could not read the uploaded file.');
        }

        // Read headers
        $this->csvHeaders = fgetcsv($handle);
        if (!$this->csvHeaders) {
            throw new \Exception('Could not read CSV headers.');
        }

        // Clean headers (remove BOM if present)
        $this->csvHeaders = array_map(function ($header) {
            return trim($header, "\xEF\xBB\xBF");
        }, $this->csvHeaders);

        // Initialize column mapping with default values
        $this->initializeColumnMapping();

        // Read all rows for preview
        $this->previewRows = [];
        $this->totalRows = 1; // Header row
        while (($row = fgetcsv($handle)) !== false) {
            $this->previewRows[] = $row;
            $this->totalRows++;
        }

        fclose($handle);
    }

    private function initializeColumnMapping()
    {
        $defaultMapping = [
            'item_name' => 'item_name',
            'item_code' => 'item_code',
            'category_name' => 'category_name',
            'menu_name' => 'menu_name',
            'price' => 'price',
            'description' => 'description',
            'type' => 'type',
            'show_on_customer_site' => 'show_on_customer_site'
        ];

        $this->columnMapping = [];
        foreach ($this->csvHeaders as $header) {
            $header = trim($header);
            $this->columnMapping[$header] = $defaultMapping[$header] ?? '';
        }
    }

    private function initializeVariationsColumnMapping()
    {
        $defaultMapping = [
            'item_code' => 'item_code',
            'variation_name' => 'variation_name',
            'variation_price' => 'variation_price',
        ];

        $variationsMapping = [];
        foreach ($this->variationsSheetHeaders as $header) {
            $header = trim($header);
            $variationsMapping[$header] = $defaultMapping[$header] ?? '';
        }
        
        // Store for later use - we'll pass this when creating the importer
        $this->columnMapping = array_merge($this->columnMapping, ['variations' => $variationsMapping]);
    }

    public function updatedUploadFile()
    {
        $this->validate([
            'uploadFile' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ], [
            'uploadFile.required' => __('modules.menu.uploadFile') . ' ' . __('app.required'),
            'uploadFile.mimes' => __('modules.menu.uploadFile') . ' ' . __('app.mustBe') . ' CSV ' . __('app.or') . ' Excel ' . __('app.file'),
            'uploadFile.max' => __('modules.menu.uploadFile') . ' ' . __('app.size') . ' ' . __('app.mustNotExceed') . ' 10MB.',
        ]);

        // Additional security checks
        if ($this->uploadFile) {
            $this->validateFileSecurity();
        }
    }

    private function validateFileSecurity()
    {
        try {
            // Check file extension against MIME type
            $allowedMimeTypes = [
                'text/csv',
                'text/plain',
                'application/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];

            $mimeType = $this->uploadFile->getMimeType();
            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new \Exception('Invalid file type detected. Please upload a valid CSV or Excel file.');
            }

            // Check file size (additional check)
            if ($this->uploadFile->getSize() > 10485760) { // 10MB in bytes
                throw new \Exception('File size exceeds maximum allowed size of 10MB.');
            }

            // Check for suspicious file names
            $filename = $this->uploadFile->getClientOriginalName();
            $suspiciousPatterns = [
                '/\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$/i',
                '/\.(exe|bat|cmd|com|scr|pif)$/i',
                '/\.(js|vbs|jar|war)$/i',
                '/\.(sql|sh|bash)$/i'
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $filename)) {
                    throw new \Exception('File type not allowed for security reasons.');
                }
            }

            // Check for null bytes or other suspicious characters in filename
            if (strpos($filename, "\0") !== false || strpos($filename, "..") !== false) {
                throw new \Exception('Invalid characters detected in filename.');
            }

            // Validate CSV content if it's a CSV file
            if (in_array($mimeType, ['text/csv', 'text/plain', 'application/csv'])) {
                $this->validateCsvContent();
            }
        } catch (\Exception $e) {
            $this->uploadFile = null;
            $this->alert('error', 'Security validation failed: ' . $e->getMessage());
            return;
        }
    }

    private function validateCsvContent()
    {
        try {
            $filePath = $this->uploadFile->getRealPath();
            $handle = fopen($filePath, 'r');

            if (!$handle) {
                throw new \Exception('Could not read file for validation.');
            }

            $lineCount = 0;
            $maxLines = 10000; // Limit to prevent memory exhaustion
            $maxColumns = 50; // Reasonable limit for menu items
            $maxCellLength = 1000; // Prevent extremely long cells

            while (($row = fgetcsv($handle)) !== false && $lineCount < $maxLines) {
                $lineCount++;

                // Check number of columns
                if (count($row) > $maxColumns) {
                    fclose($handle);
                    throw new \Exception("Too many columns detected. Maximum allowed: {$maxColumns}");
                }

                // Check each cell for suspicious content
                foreach ($row as $cell) {
                    if (strlen($cell) > $maxCellLength) {
                        fclose($handle);
                        throw new \Exception("Cell content too long. Maximum allowed: {$maxCellLength} characters");
                    }

                    // Check for potential script injections
                    $suspiciousPatterns = [
                        '/<script/i',
                        '/javascript:/i',
                        '/vbscript:/i',
                        '/onload=/i',
                        '/onerror=/i',
                        '/onclick=/i',
                        '/eval\(/i',
                        '/expression\(/i',
                        '/url\(/i',
                        '/@import/i',
                        '/<iframe/i',
                        '/<object/i',
                        '/<embed/i',
                        '/<link/i',
                        '/<meta/i'
                    ];

                    foreach ($suspiciousPatterns as $pattern) {
                        if (preg_match($pattern, $cell)) {
                            fclose($handle);
                            throw new \Exception('Potentially malicious content detected in file.');
                        }
                    }

                    // Check for null bytes
                    if (strpos($cell, "\0") !== false) {
                        fclose($handle);
                        throw new \Exception('Invalid characters detected in file content.');
                    }
                }
            }

            fclose($handle);

            // Check if file is too large (too many rows)
            if ($lineCount >= $maxLines) {
                throw new \Exception("File contains too many rows. Maximum allowed: {$maxLines}");
            }

            // Ensure file has at least a header row
            if ($lineCount < 1) {
                throw new \Exception('File appears to be empty or invalid.');
            }
        } catch (\Exception $e) {
            throw new \Exception('File content validation failed: ' . $e->getMessage());
        }
    }

    private function validateStoredFile($filePath)
    {
        try {
            $fullPath = storage_path('app/' . $filePath);

            // Check if file exists and is readable
            if (!file_exists($fullPath)) {
                throw new \Exception('File does not exist at storage location: ' . $fullPath);
            }

            if (!is_readable($fullPath)) {
                throw new \Exception('File is not readable.');
            }

            // Check file size again
            $fileSize = filesize($fullPath);
            if ($fileSize === false) {
                throw new \Exception('Could not determine file size.');
            }

            if ($fileSize > 10485760) { // 10MB
                unlink($fullPath); // Remove the file
                throw new \Exception('File size exceeds maximum allowed size.');
            }

            // Check if file is empty
            if ($fileSize === 0) {
                unlink($fullPath);
                throw new \Exception('File appears to be empty.');
            }

            // Additional MIME type check on stored file (only if finfo is available)
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $fullPath);
                    finfo_close($finfo);

                    if ($mimeType) {
                        $allowedMimeTypes = [
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ];

                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            unlink($fullPath);
                            throw new \Exception('Invalid file type detected in stored file: ' . $mimeType);
                        }
                    }
                }
            }

            // Basic file extension check as fallback
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $allowedExtensions = ['csv', 'xlsx', 'xls'];

            if (!in_array($extension, $allowedExtensions)) {
                unlink($fullPath);
                throw new \Exception('Invalid file extension: ' . $extension);
            }
        } catch (\Exception $e) {
            // Clean up file if it exists and there was an error
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }
            throw new \Exception('Stored file validation failed: ' . $e->getMessage());
        }
    }

    private function validateUploadedFile($filePath)
    {
        try {
            // Check if file exists and is readable
            if (!file_exists($filePath)) {
                throw new \Exception('File does not exist at: ' . $filePath);
            }

            if (!is_readable($filePath)) {
                throw new \Exception('File is not readable.');
            }

            // Check file size
            $fileSize = filesize($filePath);
            if ($fileSize === false) {
                throw new \Exception('Could not determine file size.');
            }

            if ($fileSize > 10485760) { // 10MB
                throw new \Exception('File size exceeds maximum allowed size.');
            }

            // Check if file is empty
            if ($fileSize === 0) {
                throw new \Exception('File appears to be empty.');
            }

            // Additional MIME type check (only if finfo is available)
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $filePath);
                    finfo_close($finfo);

                    if ($mimeType) {
                        $allowedMimeTypes = [
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ];

                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            throw new \Exception('Invalid file type detected: ' . $mimeType);
                        }
                    }
                }
            }

            // Basic file extension check as fallback
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $allowedExtensions = ['csv', 'xlsx', 'xls'];

            if (!in_array($extension, $allowedExtensions)) {
                throw new \Exception('Invalid file extension: ' . $extension);
            }
        } catch (\Exception $e) {
            throw new \Exception('File validation failed: ' . $e->getMessage());
        }
    }

    public function startImport()
    {
        // Rate limiting check
        $user = user();
        $cacheKey = 'bulk_import_' . ($user ? $user->id : 'guest');
        $lastImport = cache()->get($cacheKey);

        if ($lastImport && (time() - $lastImport) < 30) { // 1 minute cooldown
            $this->alert('error', 'Please wait before starting another import. Rate limit: 1 import per minute.');
            return;
        }

        // Validate that we're in preview stage
        if ($this->uploadStage !== 'preview') {
            $this->alert('error', __('app.invalidStage'));
            return;
        }

        // Validate required column mappings
        $requiredFields = ['item_name', 'category_name', 'menu_name', 'price'];
        foreach ($requiredFields as $field) {
            if (!in_array($field, $this->columnMapping)) {
                $this->alert('error', __('app.requiredFieldNotMapped') . ': ' . $field);
                return;
            }
        }

        try {
            $this->isImporting = true;
            $this->uploadStage = 'validating';
            $this->currentStage = __('modules.menu.validatingFile');
            $this->uploadProgress = 5;
            $this->stageProgress = 0;
            $this->importProgress = 0;
            $this->currentRow = 0;

            // Store the file temporarily with additional security
            $this->currentStage = __('modules.menu.uploadFile') . '...';
            $this->stageProgress = 50;

            // Use the file's real path (Livewire already stores it temporarily)
            $filePath = $this->uploadFile->getRealPath();

            // Check if the file exists
            if (!$filePath || !file_exists($filePath)) {
                throw new \Exception('Uploaded file not found or invalid.');
            }

            // Additional security check on the file
            $this->validateUploadedFile($filePath);

            $this->uploadProgress = 15;

            // Get restaurant and branch IDs
            $this->currentStage = __('modules.menu.importInProgress') . '...';
            $this->stageProgress = 100;

            $restaurant = restaurant();
            $branch = branch();

            if (!$restaurant || !$branch) {
                throw new \Exception('Restaurant or branch not found. Please ensure you are logged in and have proper access.');
            }

            $restaurantId = $restaurant->id;
            $branchId = $branch->id;
            $this->uploadProgress = 25;

            // Count total rows to process
            $this->currentStage = __('modules.menu.countingRows') . '...';
            $this->totalRowsToProcess = $this->totalRows;
            $this->uploadProgress = 30;

            // Start import process
            $this->uploadStage = 'processing';
            $this->currentStage = __('modules.menu.processingData');
            $this->uploadProgress = 35;

            // Extract column mappings
            $itemsColumnMapping = [];
            $variationsColumnMapping = [];
            
            foreach ($this->columnMapping as $key => $value) {
                if ($key !== 'variations' && is_string($value) && !empty($value)) {
                    $itemsColumnMapping[$key] = $value;
                } elseif ($key === 'variations' && is_array($value)) {
                    $variationsColumnMapping = $value;
                }
            }

            // Create import instance and process items sheet
            $import = new MenuItemImport($restaurantId, $branchId, $this->selectedKitchenId, $itemsColumnMapping);
            $this->currentStage = __('modules.menu.importingData') . '...';
            $this->uploadProgress = 40;

            // Process the import
            Excel::import($import, $filePath);

            // Get results from items import
            $itemsResults = $import->getResults();
            $itemsErrors = $import->getErrors();

            // Process variations sheet if it exists and has column mappings
            $variationsResults = [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
                'deleted' => 0,
            ];
            $variationsErrors = [];

            if (!empty($variationsColumnMapping)) {
                $this->uploadProgress = 65;
                $this->currentStage = __('modules.menu.importingVariations') . '...';

                $variationsImport = new MenuItemVariationImport(
                    $branchId,
                    $variationsColumnMapping,
                    $this->importMode === 'merge', // true for merge, false for replace
                    [] // itemsToReplace will be set if replace mode
                );

                Excel::import($variationsImport, $filePath);
                $variationsImport->performCleanup();
                $variationsResults = $variationsImport->getResults();
                $variationsErrors = $variationsImport->getErrors();
            }

            // Combine results
            $this->uploadProgress = 90;
            $this->currentStage = __('modules.menu.finalizingImport') . '...';

            $this->uploadResults = [
                'items_total' => $itemsResults['total'],
                'items_success' => $itemsResults['success'],
                'items_failed' => $itemsResults['failed'],
                'items_skipped' => $itemsResults['skipped'],
                'variations_total' => $variationsResults['total'],
                'variations_success' => $variationsResults['success'],
                'variations_failed' => $variationsResults['failed'],
                'variations_skipped' => $variationsResults['skipped'],
                'variations_deleted' => $variationsResults['deleted'] ?? 0,
            ];
            $this->uploadErrors = array_merge($itemsErrors, $variationsErrors ?? []);

            $this->uploadProgress = 100;
            $this->importProgress = 100;
            $this->uploadStage = 'completed';
            $this->uploadSuccess = true;
            $this->isImporting = false;

            // Update rate limiting cache
            cache()->put($cacheKey, time(), 300); // 5 minutes

            $successMessage = sprintf(
                '%s items, %s variations',
                $this->uploadResults['items_success'],
                $this->uploadResults['variations_success']
            );
            $this->alert('success', __('modules.menu.importCompleted') . '! ' . $successMessage . ' ' . __('app.added') . '.');
        } catch (\Exception $e) {
            $this->uploadStage = 'failed';
            $this->uploadErrors = [$e->getMessage()];
            $this->uploadSuccess = false;

            $this->alert('error', __('modules.menu.importFailed') . ': ' . $e->getMessage());
        }
    }


    public function downloadSampleFile()
    {
        try {
            return Excel::download(
                new MenuItemsWithVariationsTemplateExport(),
                'menu_items_with_variations_template.xlsx'
            );
        } catch (\Exception $e) {
            $this->alert('error', 'Failed to download template: ' . $e->getMessage());
        }
    }

    private function cleanupOldTempFiles()
    {
        try {
            $tempDir = storage_path('app/temp-imports');
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/import_*');
                $currentTime = time();

                foreach ($files as $file) {
                    // Delete files older than 1 hour
                    if (is_file($file) && ($currentTime - filemtime($file)) > 3600) {
                        unlink($file);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail cleanup to not disrupt user experience
        }
    }

    public function __destruct()
    {
        // Ensure cleanup of temporary files when component is destroyed
        $this->cleanupOldTempFiles();
    }

    public function render()
    {
        return view('livewire.menu.bulk-import-page');
    }
}
