<?php


namespace App\Livewire\Forms;

use App\Models\Tax;
use App\Models\Menu;
use App\Helper\Files;
use Livewire\Component;
use App\Models\KotPlace;
use App\Models\MenuItem;
use App\Models\OrderType;
use App\Models\ItemCategory;
use Livewire\WithFileUploads;
use App\Models\MenuItemPrices;
use App\Models\DeliveryPlatform;
use App\Models\MenuItemVariation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CreateMenuItem extends Component
{
    use WithFileUploads, LivewireAlert;

    protected $listeners = ['refreshCategories'];

    // Core Properties
    #[Validate('required')]
    public string $itemName = '';

    #[Validate('nullable|string|max:50')]
    public string $itemCode = '';

    #[Validate('required')]
    public string $menu = '';

    #[Validate('required')]
    public string $itemCategory = '';

    #[Validate('nullable|string')]
    public string $itemDescription = '';

    #[Validate('required|in:veg,non-veg,other,spicy,mild,sweet')]
    public string $itemType = 'veg';

    #[Validate('required|numeric|min:0')]
    public string $itemPrice = '';

    #[Validate('nullable|integer|min:0')]
    public ?int $preparationTime = null;

    #[Validate('required|boolean')]
    public bool $isAvailable = true;

    #[Validate('nullable|array')]
    public array $selectedKitchenTypes = [];

    #[Validate('nullable|image|mimes:jpeg,png,jpg,gif,svg')]
    public $itemImageTemp;

    public ?string $itemImage = null;

    // Translation Properties
    public array $translationNames = [];
    public array $translationDescriptions = [];
    public string $currentLanguage = '';
    public array $languages = [];
    public string $globalLocale = '';

    // Variation Properties - Using indexed arrays
    public array $inputs = [];
    public int $i = 0;
    public bool $hasVariations = false;
    public bool $showItemPrice = true;
    public array $variationName = [];
    public array $variationPrice = [];

    // Pricing Properties
    public array $orderTypePrices = [];
    public array $deliveryPrices = [];
    public array $platformAvailability = [];
    public string $baseDeliveryPrice = '';
    public array $variationOrderTypePrices = []; // Structure: [index => [orderTypeId => price]]
    public array $variationPlatformAvailability = []; // Structure: [index => [appId => bool]]
    public array $variationBaseDeliveryPrice = []; // Structure: [index => price]
    public array $variationDeliveryPrices = []; // Structure: [index => [appId => calculated_price]]
    // Tax Properties
    public array $selectedTaxes = [];
    public bool $taxInclusive = false;
    public ?array $taxInclusivePriceDetails = null;
    public bool $isTaxModeItem = false;
    public array $variationBreakdowns = [];

    // Modal Properties
    public bool $showMenuCategoryModal = false;

    // Collections (computed properties to avoid N+1 queries)
    public $categoryList;
    public $menus;
    public $kitchenTypes;
    public $taxes;
    public $orderTypes;
    public $deliveryApps;

    public function mount(): void
    {
        $this->initializeCollections();
        $this->initializeLanguages();
        $this->initializePricing();
        $this->initializeTaxSettings();
    }

    /**
     * Initialize collections to avoid N+1 queries
     */
    private function initializeCollections(): void
    {
        $this->categoryList = ItemCategory::all();
        $this->menus = Menu::all();
        $this->kitchenTypes = KotPlace::where('is_active', true)->get();
        $this->taxes = Tax::all();
        $this->orderTypes = OrderType::where('is_active', 1)->get();
        $this->deliveryApps = DeliveryPlatform::where('is_active', 1)->get();
    }

    /**
     * Initialize language settings
     */
    private function initializeLanguages(): void
    {
        $this->languages = languages()->pluck('language_name', 'language_code')->toArray();
        $this->globalLocale = auth()->user()->locale ?? global_setting()->locale;
        $this->currentLanguage = $this->globalLocale;
        $this->translationNames = array_fill_keys(array_keys($this->languages), '');
        $this->translationDescriptions = array_fill_keys(array_keys($this->languages), '');
    }

    /**
     * Initialize pricing arrays
     */
    private function initializePricing(): void
    {
        // Initialize order type prices
        foreach ($this->orderTypes as $orderType) {
            $this->orderTypePrices[$orderType->id] = '';
        }

        // Initialize delivery prices and availability - DEFAULT TO TRUE
        foreach ($this->deliveryApps as $app) {
            $this->deliveryPrices[$app->id] = '';
            $this->platformAvailability[$app->id] = true; // Default checked
        }
    }

    /**
     * Initialize tax settings
     */
    private function initializeTaxSettings(): void
    {
        $this->taxInclusive = (bool)(restaurant()->tax_inclusive ?? false);
        $this->isTaxModeItem = (restaurant()->tax_mode === 'item');
    }

    // VARIATION MANAGEMENT
    public function addMoreField(int $i): void
    {
        $i = $i + 1;
        $this->i = $i;
        array_push($this->inputs, $i);

        if (count($this->inputs) > 0) {
            $this->showItemPrice = false;
        }

        // Initialize pricing for new variation
        $this->initializeVariationPricing($i);
    }

    public function removeField(int $i): void
    {
        // Prevent removal if it's the last remaining variation
        if (count($this->inputs) <= 1) {
            $this->alert('warning', __('messages.invalidRequest'));
            return;
        }

        // Find the actual index in the inputs array
        $inputIndex = array_search($i, $this->inputs);

        if ($inputIndex !== false) {
            // Remove from inputs array
            unset($this->inputs[$inputIndex]);

            // Remove variation data - DO NOT reindex to maintain proper key associations
            unset($this->variationName[$i]);
            unset($this->variationPrice[$i]);
            unset($this->variationOrderTypePrices[$i]);
            unset($this->variationPlatformAvailability[$i]);
            unset($this->variationBaseDeliveryPrice[$i]);
            unset($this->variationDeliveryPrices[$i]);
            unset($this->variationBreakdowns[$i]);

            // Keep original array keys - DO NOT use array_values()
            // This ensures proper data tracking between frontend and backend
        }

        if (empty($this->inputs)) {
            $this->showItemPrice = true;
            $this->i = 0;
        }

        $this->updateVariationBreakdowns();
    }

    private function initializeVariationPricing(int $index): void
    {
        // Initialize order type prices for this variation
        if (!isset($this->variationOrderTypePrices[$index])) {
            $this->variationOrderTypePrices[$index] = [];
        }

        foreach ($this->orderTypes as $orderType) {
            $this->variationOrderTypePrices[$index][$orderType->id] = '';
        }

        // Initialize platform availability and delivery prices - DEFAULT TO TRUE
        if (!isset($this->variationPlatformAvailability[$index])) {
            $this->variationPlatformAvailability[$index] = [];
        }

        if (!isset($this->variationDeliveryPrices[$index])) {
            $this->variationDeliveryPrices[$index] = [];
        }

        foreach ($this->deliveryApps as $app) {
            $this->variationPlatformAvailability[$index][$app->id] = true;
            $this->variationDeliveryPrices[$index][$app->id] = '0.00';
        }

        // Initialize base delivery price
        if (!isset($this->variationBaseDeliveryPrice[$index])) {
            $this->variationBaseDeliveryPrice[$index] = '';
        }
    }

    /**
     * Calculate delivery prices for a specific variation
     */
    private function calculateVariationDeliveryPrices(int $index): void
    {
        if (!isset($this->variationPrice[$index])) {
            return;
        }

        // Use base delivery price from variation if set, otherwise use variation's main price
        $baseDeliveryPrice = !empty($this->variationBaseDeliveryPrice[$index])
                           ? (float)$this->variationBaseDeliveryPrice[$index]
                           : (float)($this->variationPrice[$index] ?? 0);

        foreach ($this->deliveryApps as $app) {
            $commission = (float)($app->commission_value ?? 0);
            $finalPrice = ($app->commission_type === 'percent')
                ? $baseDeliveryPrice + ($baseDeliveryPrice * $commission / 100)
                : $baseDeliveryPrice + $commission;

            $this->variationDeliveryPrices[$index][$app->id] = number_format($finalPrice, 2);
        }
    }

    public function updatedVariationPrice($value, $key): void
    {
        $this->calculateVariationDeliveryPrices((int)$key);
        $this->updateVariationBreakdowns();
    }

    /**
     * Copy the variation's standard price into all non-delivery order type fields
     * AND into the base delivery price field, then recalculate platform prices.
     * Always overwrites so the user gets a full sync when they click the button.
     */
    public function syncVariationPriceToAll(int $index): void
    {
        $price = $this->variationPrice[$index] ?? '';
        if ($price === '' || $price === null) {
            return;
        }

        foreach ($this->orderTypes as $orderType) {
            if (strtolower($orderType->slug ?? $orderType->name) === 'delivery') {
                continue;
            }
            $this->variationOrderTypePrices[$index][$orderType->id] = $price;
        }

        $this->variationBaseDeliveryPrice[$index] = $price;
        $this->calculateVariationDeliveryPrices($index);
    }

    public function updatedVariationBaseDeliveryPrice($value, $key): void
    {
        // When base delivery price is updated, recalculate delivery prices
        $this->calculateVariationDeliveryPrices((int)$key);
    }

    public function updatedVariationPlatformAvailability($value, $key): void
    {
        // Extract index and app ID from the key (format: index.appId)
        $keys = explode('.', $key);
        if (count($keys) === 2) {
            $index = (int)$keys[0];
            $this->calculateVariationDeliveryPrices($index);
        }
    }

    public function updatedVariationOrderTypePrices($value, $key): void
    {
        // Extract index and order type ID from the key (format: index.orderTypeId)
        $keys = explode('.', $key);
        if (count($keys) === 2) {
            $index = (int)$keys[0];
            // You can add any additional logic here if needed
        }
    }

    // UTILITY METHODS
    public function refreshCategories(): void
    {
        $this->categoryList = ItemCategory::all();
    }

    private function cleanupEmptyVariations(): void
    {
        // Remove any variations that have empty names or prices
        $keysToRemove = [];
        
        foreach ($this->variationName as $key => $value) {
            if (empty($value) || empty($this->variationPrice[$key])) {
                $keysToRemove[] = $key;
            }
        }
        
        // Remove identified empty variations
        foreach ($keysToRemove as $key) {
            $inputIndex = array_search($key, $this->inputs);
            if ($inputIndex !== false) {
                unset($this->inputs[$inputIndex]);
            }
            
            unset($this->variationName[$key]);
            unset($this->variationPrice[$key]);
            unset($this->variationOrderTypePrices[$key]);
            unset($this->variationPlatformAvailability[$key]);
            unset($this->variationBaseDeliveryPrice[$key]);
            unset($this->variationDeliveryPrices[$key]);
            unset($this->variationBreakdowns[$key]);
        }
        
        // DO NOT reindex - maintain original keys for proper data tracking
    }


    public function checkVariations(): void
    {
        if ($this->hasVariations) {
            $this->enableVariations();
        } else {
            $this->disableVariations();
        }
    }

    private function enableVariations(): void
    {
        $this->showItemPrice = false;
        $this->taxInclusivePriceDetails = null;
        $this->variationBreakdowns = $this->getVariationBreakdowns();

        if (empty($this->inputs) && $this->hasVariations) {
            $this->addMoreField($this->i);
        }
    }

    private function disableVariations(): void
    {
        $this->showItemPrice = true;
        $this->clearAllVariations();
    }

    private function clearAllVariations(): void
    {
        $this->variationName = [];
        $this->variationPrice = [];
        $this->variationOrderTypePrices = [];
        $this->variationPlatformAvailability = [];
        $this->variationBaseDeliveryPrice = [];
        $this->variationDeliveryPrices = [];
        $this->inputs = [];
        $this->i = 0;
    }

    // FORM SUBMISSION AND VALIDATION
    public function submitForm(): void
    {
        $traceId = (string) Str::uuid();

        Log::info('menu_item.create.submit.start', [
            'trace_id' => $traceId,
            'user_id' => auth()->id(),
            'restaurant_id' => restaurant()->id ?? null,
            'branch_id' => branch()->id ?? null,
            'has_variations' => (bool) $this->hasVariations,
            'menu_id' => $this->menu ?: null,
            'category_id' => $this->itemCategory ?: null,
            'kot_place_id' => $this->selectedKitchenTypes[0] ?? null,
            'item_code_provided' => trim((string) $this->itemCode) !== '',
            'item_name_len' => strlen((string) ($this->itemName ?? '')),
        ]);

        $this->validateForm();

        // validateForm() can add manual errors (e.g. variations) and return.
        // If there are any errors at this point, do not proceed to DB writes.
        if ($this->getErrorBag()->isNotEmpty()) {
            Log::warning('menu_item.create.submit.validation_failed', [
                'trace_id' => $traceId,
                'errors' => $this->getErrorBag()->toArray(),
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            $menuItem = $this->createMenuItem();

            Log::info('menu_item.create.created', [
                'trace_id' => $traceId,
                'menu_item_id' => $menuItem->id,
                'item_code' => $menuItem->item_code,
            ]);
            $this->handleTranslations($menuItem);
            $this->handleImageUpload($menuItem);
            $this->handleVariationsOrPricing($menuItem);
            $this->handleTaxes($menuItem);

            // Sync multi-kitchen pivot table
            if (!empty($this->selectedKitchenTypes)) {
                $pivotData = [];
                foreach ($this->selectedKitchenTypes as $index => $kitchenId) {
                    $pivotData[$kitchenId] = ['is_primary' => $index === 0];
                }
                $menuItem->kotPlaces()->sync($pivotData);
            }

            DB::commit();

            $this->handleSuccessfulSubmission();

            Log::info('menu_item.create.submit.success', [
                'trace_id' => $traceId,
                'menu_item_id' => $menuItem->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            Log::error('menu_item.create.submit.exception', [
                'trace_id' => $traceId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            $this->alert('error', __('messages.menuItemCreationFailed'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    private function validateForm(): void
    {
        // Ensure the currently edited language fields are synced into the translation arrays
        // before validation / persistence (wire:change might not fire before submit).
        $this->updateTranslation();

        // Normalize item code: treat empty string as null.
        $this->itemCode = trim((string) $this->itemCode);
        if ($this->itemCode === '') {
            $this->itemCode = '';
        }

        // If item code is empty, pre-generate so we can validate uniqueness reliably.
        if (empty($this->itemCode)) {
            $this->itemCode = $this->generateItemCode();
        }

        $this->cleanupEmptyVariations();

        if ($this->hasVariations && empty($this->variationName)) {
            $this->addError('variationName.0', __('validation.atLeastOneVariationRequired'));
            return;
        }

        // If has variations, set itemPrice to first variation price for main validation
        if ($this->hasVariations && !empty($this->variationPrice)) {
            $this->itemPrice = reset($this->variationPrice) ?: '0';
        }

        $branch = branch();
        $itemCodeRule = Rule::unique('menu_items', 'item_code')
            ->when($branch, fn($rule) => $rule->where('branch_id', $branch->id));

        $rules = [
            'translationNames.' . $this->globalLocale => 'required',
            'baseDeliveryPrice' => 'nullable|numeric|min:0',
            'itemCategory' => 'required',
            'menu' => 'required',
            'itemCode' => ['nullable', 'string', 'max:50', $itemCodeRule],
            'isAvailable' => 'required|boolean',
            'orderTypePrices.*' => 'nullable|numeric|min:0',
            'platformAvailability.*' => 'nullable|boolean',
        ];

        // If Kitchen module is enabled, at least one kitchen type is mandatory.
        if (in_array('Kitchen', restaurant_modules(), true)) {
            $rules['selectedKitchenTypes'] = ['required', 'array', 'min:1'];
            $rules['selectedKitchenTypes.*'] = ['exists:kot_places,id'];
        }

        // Add validation rules for variations if they exist
        if ($this->hasVariations && !empty($this->variationName)) {
            $rules['variationName.*'] = 'required|string|max:255';
            $rules['variationPrice.*'] = 'required|numeric|min:0';
            $rules['variationOrderTypePrices.*.*'] = 'nullable|numeric|min:0';
            $rules['variationBaseDeliveryPrice.*'] = 'nullable|numeric|min:0';
        }

        // Only require itemPrice if not using variations
        if (!$this->hasVariations) {
            $rules['itemPrice'] = 'required|numeric|min:0';
        }

        $this->validate($rules, $this->getValidationMessages());
    }

    private function getValidationMessages(): array
    {
        return [
            'baseDeliveryPrice.numeric' => __('validation.baseDeliveryPriceMustBeNumeric'),
            'baseDeliveryPrice.min' => __('validation.baseDeliveryPriceMustBePositive'),
            'translationNames.' . $this->globalLocale . '.required' =>
                __('validation.itemNameRequired', ['language' => $this->languages[$this->globalLocale]]),
            'itemPrice.required_if' => __('validation.itemPriceRequired'),
            'itemPrice.numeric' => __('validation.itemPriceMustBeNumeric'),
            'itemPrice.min' => __('validation.itemPriceMustBePositive'),
            'selectedKitchenTypes.required' => __('validation.kitchenTypeRequired'),
            'selectedKitchenTypes.min' => __('validation.kitchenTypeRequired'),
            'selectedKitchenTypes.*.exists' => __('validation.kitchenTypeInvalid'),
        ];
    }

    private function createMenuItem(): MenuItem
    {
        $userSuppliedCode = trim((string) $this->itemCode) !== '';

        if (! $userSuppliedCode) {
            $this->itemCode = $this->generateItemCode();
        }

        $maxAttempts = 15;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return MenuItem::create([
                    'item_name' => $this->translationNames[$this->globalLocale],
                    'item_code' => $this->itemCode,
                    'price' => $this->hasVariations ? 0 : (float) $this->itemPrice,
                    'item_category_id' => $this->itemCategory,
                    'description' => $this->translationDescriptions[$this->globalLocale],
                    'is_available' => $this->isAvailable,
                    'type' => $this->itemType,
                    'menu_id' => $this->menu,
                    'preparation_time' => $this->preparationTime,
                    'kot_place_id' => $this->selectedKitchenTypes[0] ?? null,
                    'tax_inclusive' => $this->isTaxModeItem ? $this->taxInclusive : false,
                ]);
            } catch (QueryException $e) {
                if ($userSuppliedCode || ! MenuItem::isDuplicateBranchItemCodeException($e)) {
                    throw $e;
                }
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                $branch = branch();
                if (! $branch) {
                    throw $e;
                }
                $this->itemCode = MenuItem::generateNextItemCodeForBranch((int) $branch->id);
            }
        }

        throw new \RuntimeException('Unable to allocate a unique item code.');
    }

    /**
     * Generate unique item code scoped to the current branch.
     *
     * Bypasses only AvailableMenuItemScope so unavailable items are still
     * counted, while BranchScope remains active to keep codes branch-scoped.
     * DB unique index on (branch_id, item_code) plus retry on insert in
     * {@see createMenuItem()} handles concurrent allocation.
     */
    private function generateItemCode(): string
    {
        $branch = branch();
        if (! $branch) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'itemCode' => 'Branch context is required to generate an item code.',
            ]);
        }

        return MenuItem::generateNextItemCodeForBranch((int) $branch->id);
    }

    private function handleTranslations(MenuItem $menuItem): void
    {
        $translations = collect($this->translationNames)
            ->filter(fn($name, $locale) => !empty($name) || !empty($this->translationDescriptions[$locale]))
            ->map(fn($name, $locale) => [
                'locale' => $locale,
                'item_name' => $name,
                'description' => $this->translationDescriptions[$locale] ?? ''
            ])->values()->all();

        if (!empty($translations)) {
            $menuItem->translations()->createMany($translations);
        }
    }

    private function handleImageUpload(MenuItem $menuItem): void
    {
        if ($this->itemImageTemp) {
            $menuItem->update([
                'image' => Files::uploadLocalOrS3($this->itemImageTemp, 'item', width: 350),
            ]);
        }
    }

    private function handleVariationsOrPricing(MenuItem $menuItem): void
    {
        if ($this->hasVariations) {
            $this->createVariations($menuItem);
        } else {
            $this->savePricingData($menuItem->id);
        }
    }

    private function createVariations(MenuItem $menuItem): void
    {
        $validVariations = 0;

        foreach ($this->variationName as $key => $value) {
            if (!empty($value) && isset($this->variationPrice[$key]) && !empty($this->variationPrice[$key])) {
                $this->validate([
                    'variationPrice.' . $key => 'required|numeric'
                ], [
                    'variationPrice.' . $key . '.required' => __('validation.variationPriceRequired'),
                ]);

                $variationModel = MenuItemVariation::create([
                    'menu_item_id' => $menuItem->id,
                    'variation' => $value,
                    'price' => (float)$this->variationPrice[$key],
                ]);

                // Save pricing data for this variation
                $this->savePricingData($menuItem->id, $variationModel->id, $key);
                $validVariations++;
            }
        }

        if ($validVariations === 0) {
            throw new \Exception(__('validation.atLeastOneVariationRequired'));
        }
    }

    private function handleTaxes(MenuItem $menuItem): void
    {
        if ($this->isTaxModeItem && !empty($this->selectedTaxes)) {
            $menuItem->taxes()->sync($this->selectedTaxes);
        }
    }

    private function handleSuccessfulSubmission(): void
    {
        $this->resetForm();

        $this->dispatch('hideAddMenuItem');
        $this->dispatch('menuItemAdded');
        $this->dispatch('refreshCategories');

        $this->redirect(route('menu-items.index'), true);

        $this->alert('success', __('messages.menuItemAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function resetForm()
    {
        $this->itemName = '';
        $this->menu = '';
        $this->translationNames = array_fill_keys(array_keys($this->languages), '');
        $this->translationDescriptions = array_fill_keys(array_keys($this->languages), '');
        $this->itemCategory = '';
        $this->itemPrice = '';
        $this->itemDescription = '';
        $this->itemType = 'veg';
        $this->itemImage = null;
        $this->itemImageTemp = null;
        $this->preparationTime = null;
        $this->variationName = [];
        $this->variationPrice = [];
        $this->variationOrderTypePrices = [];
        $this->variationPlatformAvailability = [];
        $this->variationBaseDeliveryPrice = [];
        $this->variationDeliveryPrices = [];
        $this->variationBreakdowns = [];
        $this->taxInclusivePriceDetails = null;
        $this->inputs = [];
        $this->i = 0;
        $this->showItemPrice = true;
        $this->hasVariations = false;
        $this->selectedTaxes = [];

        // Reset pricing properties
        $this->baseDeliveryPrice = '';
        $this->deliveryPrices = [];
        $this->orderTypePrices = [];
        $this->platformAvailability = [];

        foreach ($this->orderTypes as $orderType) {
            $this->orderTypePrices[$orderType->id] = '';
        }
        foreach ($this->deliveryApps as $app) {
            $this->deliveryPrices[$app->id] = '';
            $this->platformAvailability[$app->id] = true;
        }
    }

    public function updateTranslation()
    {
        $this->translationNames[$this->currentLanguage] = $this->itemName;
        $this->translationDescriptions[$this->currentLanguage] = $this->itemDescription;
    }

    public function updatedCurrentLanguage()
    {
        $this->itemName = $this->translationNames[$this->currentLanguage];
        $this->itemDescription = $this->translationDescriptions[$this->currentLanguage];
    }

    public function showMenuCategoryModal()
    {
        $this->dispatch('showMenuCategoryModal');
    }

    public function updatedTaxInclusive()
    {
        $this->recalculateTaxBreakdowns();
    }

    public function updatedItemPrice(): void
    {
        $this->recalculateTaxBreakdowns();
    }

    public function updatedSelectedTaxes(): void
    {
        $this->recalculateTaxBreakdowns();
    }

    private function recalculateTaxBreakdowns(): void
    {
        if ($this->hasVariations) {
            $this->variationBreakdowns = $this->getVariationBreakdowns();
            $this->taxInclusivePriceDetails = null;
        } else {
            $this->taxInclusivePriceDetails = $this->getTaxInclusivePriceDetailsProperty();
            $this->variationBreakdowns = [];
        }
    }

    public function updatedItemImageTemp()
    {
        $this->itemImage = null;

        try {
            $this->validateImage();
        } catch (\League\Flysystem\UnableToRetrieveMetadata $e) {
            $this->itemImageTemp = null;
            $this->addError('itemImageTemp', 'The image could not be processed. Please rename the file (avoid very long filenames) and try again.');
        } catch (\Throwable $e) {
            $this->itemImageTemp = null;
            $this->addError('itemImageTemp', 'The image could not be uploaded. Please try again with a different file.');
        }
    }

    public function removeSelectedImage()
    {
        $this->itemImageTemp = null;
        $this->itemImage = null;
    }

    public function validateImage()
    {
        if (!$this->itemImageTemp) return;

        try {
            $this->validate([
                'itemImageTemp' => 'image|mimes:jpeg,png,jpg,gif,svg',
            ]);
        } catch (\League\Flysystem\UnableToRetrieveMetadata $e) {
            $this->itemImageTemp = null;
            $this->addError('itemImageTemp', 'The image could not be processed. Please rename the file (avoid very long filenames) and try again.');
            return;
        }

        // Use native filesize() via getRealPath() to avoid Livewire's livewire-tmp disk lookup,
        // which can fail on some hosting environments (UnableToRetrieveMetadata).
        $realPath = $this->itemImageTemp->getRealPath();
        $sizeInBytes = null;
        if ($realPath && file_exists($realPath)) {
            $sizeInBytes = filesize($realPath);
        } else {
            $fallbackSize = $this->itemImageTemp->getSize();
            if (is_numeric($fallbackSize) && (int) $fallbackSize > 0) {
                $sizeInBytes = (int) $fallbackSize;
            }
        }

        if ($sizeInBytes === null) {
            $this->addError('itemImageTemp', 'Unable to validate image size');
            $this->itemImageTemp = null;
            return;
        }

        $sizeInKb = $sizeInBytes / 1024;
        if ($sizeInKb > 2048) {
            $this->addError('itemImageTemp', 'The image must not be greater than 2MB.');
            $this->itemImageTemp = null;
            return;
        }

        // Check image dimensions
        $imageInfo = $realPath ? @getimagesize($realPath) : false;
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Recommend minimum dimensions
            if ($width < 200 || $height < 200) {
                $this->addError('itemImageTemp', 'Image dimensions are too small. Recommended minimum: 200x200 pixels.');
            }
        }
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['bytes', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }

    // TAX CALCULATIONS
    #[Computed]
    public function getTaxInclusivePriceDetailsProperty(): ?array
    {
        if (empty($this->itemPrice) || !$this->isTaxModeItem) {
            return null;
        }

        return (new MenuItem)->getTaxBreakdown(
            (float)$this->itemPrice,
            $this->selectedTaxes,
            $this->taxInclusive
        );
    }

    private function getVariationBreakdowns(): array
    {
        if (!$this->isTaxModeItem) {
            return [];
        }

        $breakdowns = [];
        foreach ($this->variationPrice as $key => $price) {
            if (!empty($price)) {
                $breakdowns[$key] = [
                    'name' => $this->variationName[$key] ?? '',
                    'breakdown' => (new MenuItem)->getTaxBreakdown(
                        (float)$price,
                        $this->selectedTaxes,
                        $this->taxInclusive
                    )
                ];
            }
        }
        return $breakdowns;
    }

    private function updateVariationBreakdowns(): void
    {
        $this->variationBreakdowns = $this->getVariationBreakdowns();
    }

    // PRICING MANAGEMENT
    public function updatedBaseDeliveryPrice(): void
    {
        $this->calculateDeliveryPrices();
    }

    public function updatedOrderTypePrices(): void
    {
        if (!$this->hasVariations) {
            // Find first non-empty price to use as base price
            foreach ($this->orderTypePrices as $price) {
                if (!empty($price) && $price > 0) {
                    $this->itemPrice = $price;
                    $this->baseDeliveryPrice = $price;
                    $this->calculateDeliveryPrices();
                    break;
                }
            }
        }
    }

    private function calculateDeliveryPrices(): void
    {
        $basePrice = !empty($this->baseDeliveryPrice)
            ? (float)$this->baseDeliveryPrice
            : (!empty($this->itemPrice) ? (float)$this->itemPrice : 0);

        foreach ($this->deliveryApps as $app) {
            $commission = (float)($app->commission_value ?? 0);
            $finalPrice = ($app->commission_type === 'percent')
                ? $basePrice + ($basePrice * $commission / 100)
                : $basePrice + $commission;
            $this->deliveryPrices[$app->id] = number_format($finalPrice, 2);
        }
    }

    /**
     * Save pricing data for menu item or variation
     */
    private function savePricingData(int $menuItemId, ?int $variationId = null, ?int $localIndex = null): void
    {
        if ($variationId !== null && $localIndex !== null) {
            $this->saveVariationPricingData($menuItemId, $variationId, $localIndex);
        } else {
            $this->saveItemPricingData($menuItemId);
        }
    }

    private function saveVariationPricingData(int $menuItemId, int $variationId, int $localIndex): void
    {
        if (!isset($this->variationPrice[$localIndex])) return;

        $basePrice = (float)$this->variationPrice[$localIndex];
        $orderTypePrices = $this->variationOrderTypePrices[$localIndex] ?? [];
        $baseDeliveryPrice = $this->variationBaseDeliveryPrice[$localIndex] ?? '';

        $this->createPricingRecords($menuItemId, $basePrice, $orderTypePrices, $baseDeliveryPrice, $variationId, $localIndex);
    }

    private function saveItemPricingData(int $menuItemId): void
    {
        $basePrice = (float)$this->itemPrice;
        $this->createPricingRecords($menuItemId, $basePrice, $this->orderTypePrices, $this->baseDeliveryPrice);
    }

    private function createPricingRecords(
        int $menuItemId,
        float $basePrice,
        array $orderTypePrices,
        string $baseDeliveryPrice,
        ?int $variationId = null,
        ?int $localIndex = null
    ): void {
        // Save order type pricing (excluding delivery)
        foreach ($this->orderTypes as $orderType) {

            $orderTypePrice = !empty($orderTypePrices[$orderType->id]) ? (float)$orderTypePrices[$orderType->id] : $basePrice;

            if (strtolower($orderType->slug ?? $orderType->name) === 'delivery') {
                $deliveryBase = !empty($baseDeliveryPrice) ? (float)$baseDeliveryPrice : $basePrice;
                $orderTypePrice = $deliveryBase;
            }

            MenuItemPrices::create([
                'menu_item_id' => $menuItemId,
                'order_type_id' => $orderType->id,
                'delivery_app_id' => null,
                'menu_item_variation_id' => $variationId,
                'calculated_price' => $orderTypePrice,
                'final_price' => $orderTypePrice,
                'status' => true,
            ]);
        }

        // Save delivery platform pricing
        $this->saveDeliveryPlatformPricing($menuItemId, $basePrice, $variationId, $baseDeliveryPrice, $localIndex);
    }

    private function saveDeliveryPlatformPricing(int $menuItemId, float $basePrice, ?int $variationId = null, string $baseDeliveryPrice = '', ?int $localIndex = null): void
    {
        $deliveryOrderType = $this->orderTypes->where('slug', 'delivery')->first();

        if (!$deliveryOrderType) return;

        foreach ($this->deliveryApps as $app) {
            // Determine availability - for variations, check variation-specific availability
            // Default to TRUE if not explicitly set to false
            $isAvailable = true;

            if ($localIndex !== null) {
                // For variations - check if the platform is available (defaults to true)
                $isAvailable = isset($this->variationPlatformAvailability[$localIndex][$app->id]) 
                    ? (bool)$this->variationPlatformAvailability[$localIndex][$app->id] 
                    : true;
            } else {
                // For regular items - check if the platform is available (defaults to true)
                $isAvailable = isset($this->platformAvailability[$app->id]) 
                    ? (bool)$this->platformAvailability[$app->id] 
                    : true;
            }

            // Get the base delivery price for calculation
            $deliveryBase = $basePrice; // Default to variation/item price

            if ($localIndex !== null) {
                // For variations, check if base delivery price is set
                if (!empty($this->variationBaseDeliveryPrice[$localIndex])) {
                    $deliveryBase = (float)$this->variationBaseDeliveryPrice[$localIndex];
                }
            } else {
                // For regular items
                if (!empty($baseDeliveryPrice)) {
                    $deliveryBase = (float)$baseDeliveryPrice;
                } elseif (!empty($this->baseDeliveryPrice)) {
                    $deliveryBase = (float)$this->baseDeliveryPrice;
                }
            }

            $commission = (float)($app->commission_value ?? 0);
            $calculatedPrice = ($app->commission_type === 'percent')
                ? $deliveryBase + ($deliveryBase * $commission / 100)
                : $deliveryBase + $commission;

            MenuItemPrices::create([
                'menu_item_id' => $menuItemId,
                'order_type_id' => $deliveryOrderType->id,
                'delivery_app_id' => $app->id,
                'menu_item_variation_id' => $variationId,
                'calculated_price' => $deliveryBase,
                'final_price' => $calculatedPrice,
                'status' => $isAvailable,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.forms.create-menu-item');
    }
}

