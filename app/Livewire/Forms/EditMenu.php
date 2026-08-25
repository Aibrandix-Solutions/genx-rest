<?php
namespace App\Livewire\Forms;

use App\Livewire\Concerns\ManagesMenuBranchSelection;
use App\Models\Menu;
use App\Scopes\BranchScope;
use App\Services\MenuBranchProvisioningService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditMenu extends Component
{
    use LivewireAlert;
    use ManagesMenuBranchSelection;

    public $menuName;
    public $activeMenu;
    public $translations = [];
    public $currentLanguage;
    public $globalLocale;
    public $languages = [];
    public array $linkedBranchNames = [];

    public function mount()
    {
        $this->languages = collect(languages())->pluck('language_name', 'language_code')->toArray();
        $this->globalLocale = global_setting()->locale;
        $this->currentLanguage = $this->globalLocale;
        $this->initializeMenuBranchSelection();
        // Load existing translations
        $this->translations = $this->activeMenu->getTranslations('menu_name') ?? [];

        // Ensure all languages are available
        foreach ($this->languages as $code => $name) {
            if (!isset($this->translations[$code])) {
                $this->translations[$code] = '';
            }
        }

        $this->menuName = $this->translations[$this->currentLanguage] ?? '';
        $this->linkedBranchNames = $this->loadLinkedBranchNames();
    }

    public function submitForm()
    {
        $this->validate(array_merge([
            'translations.' . $this->globalLocale => 'required',
        ], $this->additionalBranchSelectionRules()), [
            'translations.' . $this->globalLocale . '.required' => __('validation.menuNameRequired', ['language' => $this->languages[$this->globalLocale]]),
        ]);

        // Save translations using Spatie
        collect($this->translations)
            ->each(function ($translation, $languageCode) {
            empty(trim($translation))
                ? $this->activeMenu->forgetTranslation('menu_name', $languageCode)
                : $this->activeMenu->setTranslation('menu_name', $languageCode, $translation);
            });

        $this->activeMenu->save();

        $additional = array_values(array_diff(
            array_map('intval', $this->additionalBranchIds),
            [(int) $this->activeMenu->branch_id]
        ));

        if ($additional !== []) {
            $groupUuid = app(MenuBranchProvisioningService::class)->ensureCatalogGroupUuid($this->activeMenu);
            app(MenuBranchProvisioningService::class)->provisionMenus(
                $this->activeMenu->getTranslations('menu_name'),
                $additional,
                $groupUuid
            );
        }

        $this->alert('success', __('messages.menuUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('hideEditMenu');
    }

    protected function loadLinkedBranchNames(): array
    {
        if (! $this->activeMenu->catalog_group_uuid) {
            return [$this->activeMenu->branch->name ?? branch()?->name];
        }

        return Menu::withoutGlobalScope(BranchScope::class)
            ->where('catalog_group_uuid', $this->activeMenu->catalog_group_uuid)
            ->with('branch:id,name')
            ->get()
            ->pluck('branch.name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function updateTranslation()
    {
        $this->translations[$this->currentLanguage] = $this->menuName;
    }

    public function removeTranslation($languageCode)
    {
        if (in_array($languageCode, [$this->globalLocale])) {
            $this->alert('error', __('validation.cannotRemoveTranslation', ['language' => $this->languages[$this->globalLocale]]));
            return;
        }

        $this->translations[$languageCode] = null;
        $this->menuName = $this->translations[$this->currentLanguage] ?? '';
    }

    public function updatedCurrentLanguage()
    {
        $this->menuName = $this->translations[$this->currentLanguage] ?? '';
    }

    public function render()
    {
        return view('livewire.forms.edit-menu');
    }
}
