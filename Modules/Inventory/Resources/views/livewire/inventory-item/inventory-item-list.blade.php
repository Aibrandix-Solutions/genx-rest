<div>
    <div>

        <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <div class="mb-4">
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">@lang('inventory::modules.menu.inventoryItems')</h1>
                </div>
                <div class="items-center justify-between block sm:flex ">
                    <div class="lg:flex items-center mb-4 sm:mb-0 gap-4">
                        <form class="sm:pr-3" action="#" method="GET">
                            <label for="products-search" class="sr-only">Search</label>
                            <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                                <x-input id="menu_name" class="block mt-1 w-full" type="text" placeholder="{{ __('inventory::placeholders.searchInventoryItem') }}" wire:model.live.debounce.500ms="search"  />
                            </div>
                        </form>

                        <div>
                            <x-dropdown align="left">
                                <x-slot name="trigger">
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                            @lang('app.perPage')
                                            @if ($perPage != 20)
                                            <div class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-md dark:border-gray-900 ml-1">{{ $perPage }}</div>
                                            @endif
                                            <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                            </svg>
                                        </button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="block px-4 py-2 text-sm font-medium text-gray-500">
                                        <h6 class="text-sm font-medium text-gray-900 dark:text-white">
                                            @lang('app.perPage')
                                        </h6>
                                    </div>
                                    
                                    @foreach ([20, 50, 100, 200] as $items)
                                    <x-dropdown-link class="flex items-center">
                                        <input id="per-page-{{ $items }}" type="radio" value="{{ $items }}" wire:model.live='perPage'
                                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" />
                                        <label for="per-page-{{ $items }}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $items }} @lang('app.items')
                                        </label>
                                    </x-dropdown-link>
                                    @endforeach

                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>

                    <div class="lg:inline-flex items-center gap-4">
                        <x-secondary-button wire:click="export" wire:loading.attr="disabled">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            @lang('app.export')
                        </x-secondary-button>

                        <x-secondary-button wire:click="downloadImportTemplate" wire:loading.attr="disabled">
                            Download Import Template
                        </x-secondary-button>

                        <label class="inline-flex items-center px-3 py-2 text-sm font-medium border rounded-md cursor-pointer border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Import File
                            <input type="file" class="hidden" wire:model="importFile" accept=".xlsx,.xls,.csv,.txt">
                        </label>

                        <x-button type="button" wire:click="importItems" wire:loading.attr="disabled" wire:target="importFile,importItems">
                            Upload
                        </x-button>

                        @if(user_can('Create Inventory Item'))
                        <x-button type='button' wire:click="$set('showAddInventoryItem', true)" >@lang('inventory::modules.inventoryItem.addInventoryItem')</x-button>
                        @endif
                    </div>

                </div>
                @error('importFile')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror


            </div>

        </div>

        <livewire:inventory::inventory-item.inventory-item-table :search='$search' :perPage='$perPage' key='inventory-item-table-{{ microtime() }}' />


    </div>

    
    <x-right-modal wire:model.live="showAddInventoryItem">
        <x-slot name="title">
            @lang("inventory::modules.inventoryItem.addInventoryItem")
        </x-slot>

        <x-slot name="content">
            <livewire:inventory::inventory-item.add-inventory-item />
        </x-slot>
    </x-right-modal>

</div>
