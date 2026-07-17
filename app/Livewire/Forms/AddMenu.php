<?php

namespace App\Livewire\Forms;

use App\Livewire\Concerns\ManagesMenuBranchSelection;
use App\Models\Menu;
use App\Services\MenuBranchProvisioningService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddMenu extends Component
{
    use LivewireAlert;
    use ManagesMenuBranchSelection;

    public $menuName = '';
    public $translations = [];
    public $languages = [];
    public $currentLanguage;
    public $globalLocale;

    public function mount()
    {
        $this->languages = languages()->pluck('language_name', 'language_code')->toArray();
        $this->translations = array_fill_keys(array_keys($this->languages), '');
        $this->globalLocale = global_setting()->locale;
        $this->currentLanguage = $this->globalLocale;
        $this->initializeMenuBranchSelection();
    }

    public function updateTranslation()
    {
        $this->translations[$this->currentLanguage] = $this->menuName;
    }

    public function updatedCurrentLanguage()
    {
        $this->menuName = $this->translations[$this->currentLanguage] ?? '';
    }


    public function submitForm()
    {
        $this->validate(array_merge([
            'translations.' . $this->globalLocale => 'required',
        ], $this->menuBranchSelectionRules()), [
            'translations.' . $this->globalLocale . '.required' => __('validation.menuNameRequired', ['language' => $this->languages[$this->globalLocale]]),
        ]);

        $filteredTranslations = array_filter($this->translations, 'trim');

        app(MenuBranchProvisioningService::class)->provisionMenus(
            $filteredTranslations,
            $this->selectedBranchIds
        );

        // Reset the value
        $this->menuName = '';
        $this->translations = array_fill_keys(array_keys($this->translations), '');
        $this->initializeMenuBranchSelection();

        $this->dispatch('menuAdded');
        $this->dispatch('refreshMenus');

        $this->alert('success', __('messages.menuAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.add-menu');
    }

}
