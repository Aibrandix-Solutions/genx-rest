<div
    x-data="{
        open: false,
        categoryName: '',
        items: [],
        show(name, items) {
            this.categoryName = name;
            this.items = items;
            this.open = true;
        },
        close() {
            this.open = false;
        }
    }"
    @keydown.escape.window="close()"
>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">@lang('menu.itemCategories')</h1>
            </div>
            <div class="items-center justify-between block sm:flex ">
                <div class="flex items-center mb-4 sm:mb-0">
                    <form class="sm:pr-3" action="#" method="GET">
                        <label for="products-search" class="sr-only">Search</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="menu_name" class="block mt-1 w-full" type="text"
                                placeholder="{{ __('placeholders.searchItemCategory') }}"
                                wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>
                </div>
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-secondary-link href="{{ route('menu-items.entities.sort') }}">
                        @lang('modules.menu.sortMenuItems')
                    </x-secondary-link>
                    @if(user_can('Create Item Category'))
                    <x-button type='button' wire:click="$toggle('showMenuCategoryModal')">@lang('modules.menu.addItemCategory')</x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.itemCategory')
                                </th>
                                <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.allMenuItems')
                                </th>
                                <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('app.action')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" wire:key='menu-item-list-{{ microtime() }}'>
                            @forelse ($categories as $item)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700"
                                wire:key='menu-item-{{ $item->id . microtime() }}'
                                wire:loading.class.delay='opacity-10'>
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->category_name }}
                                </td>
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    @if($item->items_count > 0)
                                        <button
                                            type="button"
                                            @click="show({{ Js::from($item->category_name) }}, {{ Js::from($item->menuItemsForPreview) }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-800/60 transition"
                                            title="Click to view items"
                                        >
                                            {{ $item->items_count }} @lang('modules.menu.item')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                            0 @lang('modules.menu.item')
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Update Item Category'))
                                    <x-secondary-button-table wire:click='showEditCategory({{ $item->id }})' wire:key='edit-cat-button-{{ $item->id }}'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        @lang('app.update')
                                    </x-secondary-button-table>
                                    @endif
                                    @if(user_can('Delete Item Category'))
                                    <x-danger-button-table wire:click="showDeleteCategory({{ $item->id }})">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </x-danger-button-table>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 space-x-6 dark:text-gray-400" colspan="5">
                                    @lang('messages.noItemCategoryAdded')
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div wire:key='menu-item-category-paginate-{{ microtime() }}'
        class="sticky bottom-0 right-0 items-center w-full p-4 bg-white border-t border-gray-200 sm:flex sm:justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0 w-full">
            {{ $categories->links() }}
        </div>
    </div>

    {{-- ── Alpine-powered items modal — instant, zero Livewire round-trip ── --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50"
            @click="close()"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Panel --}}
        <div
            class="relative z-10 w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-2xl flex flex-col max-h-[80vh]"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="categoryName"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        <span x-text="items.length"></span>&nbsp;item<span x-show="items.length !== 1">s</span>
                    </p>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition"
                    aria-label="Close"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1">
                <template x-if="items.length > 0">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-8">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Menu Item</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item Code</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="(row, i) in items" :key="i">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500" x-text="i + 1"></td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" x-text="row.name"></td>
                                    <td class="px-4 py-3">
                                        <span
                                            x-show="row.item_code"
                                            x-text="row.item_code"
                                            class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded"
                                        ></span>
                                        <span x-show="!row.item_code" class="text-gray-300 dark:text-gray-600">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            x-text="row.has_variations ? 'See variations' : (row.raw_price > 0 ? row.price : '--')"
                                            :class="(row.has_variations || row.raw_price <= 0)
                                                ? 'text-xs text-gray-400 dark:text-gray-500 italic'
                                                : 'font-semibold text-indigo-600 dark:text-indigo-400'"
                                        ></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
                <template x-if="items.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm">No items in this category</p>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end flex-shrink-0">
                <button
                    type="button"
                    @click="close()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition"
                >
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <x-dialog-modal wire:model.live="showEditItemCategory">
        <x-slot name="title">{{ __("modules.menu.itemCategory") }}</x-slot>
        <x-slot name="content">
            @if ($itemCategory)
            @livewire('forms.editItemCategory', ['itemCategory' => $itemCategory], key(str()->random(50)))
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditItemCategory', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showMenuCategoryModal" maxWidth="xl">
        <x-slot name="title">@lang('modules.menu.addItemCategory')</x-slot>
        <x-slot name="content">
            @livewire('forms.addItemCategory')
        </x-slot>
        <x-slot name="footer">
            <x-button-cancel wire:click="$toggle('showMenuCategoryModal')" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-button-cancel>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model="confirmDeleteCategory">
        <x-slot name="title">@lang('modules.menu.deleteItemCategory')?</x-slot>
        <x-slot name="content">@lang('modules.menu.deleteCategoryMessage')</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteCategory')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>
            @if ($itemCategory)
            <x-danger-button class="ml-3" wire:click='deleteItemCategory({{ $itemCategory->id }})' wire:loading.attr="disabled">
                {{ __('app.delete') }}
            </x-danger-button>
            @endif
        </x-slot>
    </x-confirmation-modal>

</div>
