<div class="w-full">
    <div x-data="{ showMenu: false }">
        <!-- Mobile Toggle Button -->
        <button
            @click="showMenu = !showMenu"
            class="fixed bottom-6 right-6 z-50 md:hidden bg-skin-base text-white rounded-full shadow-lg p-4 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-skin-base transition"
            aria-label="Toggle Menu"
            type="button"
        >
            <svg x-show="!showMenu" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <svg x-show="showMenu" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Menu Panel -->
        <div :class="{'hidden': !showMenu, ' inset-0 z-40 flex': showMenu}" class="md:flex flex-col bg-gray-50 lg:h-full w-full py-4 px-3 dark:bg-gray-900 transition-transform duration-300 md:static md:inset-auto md:z-auto md:translate-x-0 overflow-y-auto md:overflow-visible md:max-h-none" style="backdrop-filter: blur(2px);" x-cloak>
            {{-- Search and Reset Section --}}
            <div class="flex items-center justify-between gap-3">
                <div class="flex-1">
                    <form action="#" method="GET">
                        <label for="products-search" class="sr-only">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <x-input class="block w-full pl-10 pr-3 py-2 border-gray-200 rounded-lg text-sm" type="text"
                                placeholder="{{ __('placeholders.searchMenuItems') }}"
                                wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>
                </div>

                <x-primary-link href="{{ route('pos.index') }}"
                    class="inline-flex items-center px-3 py-2 gap-1 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z" />
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466" />
                    </svg>
                    @lang('app.reset')
                </x-primary-link>
            </div>

            <div class="flex gap-2 mt-4 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 flex-wrap">

                <button wire:click="$set('menuId', null)" @class([

                    'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',

                    'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => is_null($menuId),

                    'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => !is_null($menuId),

                ])>

                    @lang('app.showAll')

                </button> 

                @foreach ($menuList as $index => $item)

                    <button wire:click="$set('menuId', {{ $item->id }})" @class([

                        'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',

                        'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $menuId == $item->id,

                        'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $menuId != $item->id,

                    ])>

                        {{ $item->getTranslation('menu_name', session('locale', app()->getLocale())) }}

                    </button>

                @endforeach

            </div>

            {{-- Categories Section --}}
            <div class="flex gap-2 mt-4 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 flex-wrap">
                <button wire:click="$set('filterCategories', null)" @class([
                    'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',
                    'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => is_null($filterCategories),
                    'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => !is_null($filterCategories),
                ])>
                    @lang('app.showAll')
                </button>
                @foreach ($categoryList as $value)
                    <button wire:click="$set('filterCategories', {{ $value->id }})" @class([
                        'px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap',
                        'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $filterCategories == $value->id,
                        'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $filterCategories != $value->id,
                    ])>
                        {{ $value->category_name }}
                    </button>
                @endforeach
            </div>

            {{-- Menu Items Grid --}}
            <div class="mt-4">
                <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-8 gap-3">
                    @forelse ($menuItems as $item)
                        <li class="group relative">
                            <input type="checkbox" id="item-{{ $item->id }}" value="{{ $item->id }}"
                                wire:key='item-input-{{ $item->id }}'
                                class="hidden peer">
                            <label for="item-{{ $item->id }}"
                                onclick='window.posClient?.queueAddItem({ id: {{ $item->id }}, variationCount: {{ $item->variations_count }}, modifierCount: {{ $item->modifier_groups_count }} });'
                                @class([
                                    "block w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 dark:hover:bg-gray-700/30 cursor-pointer relative hover:shadow-md dark:bg-gray-800 dark:border-gray-700
                        active:scale-95 focus-visible:scale-95 focus-visible:ring-2 focus-visible:ring-skin-base outline-none",
                                    "bg-gray-100 dark:bg-gray-800" => !$item->in_stock,
                                    "bg-white dark:bg-gray-900" => $item->in_stock,
                                ])

                                tabindex="0"
                    >
                                {{-- Image Section --}}
                                @if (restaurant() && !restaurant()->hide_menu_item_image_on_pos)
                                <div class="relative aspect-square hidden md:block">
                                    <img class="w-full h-full object-cover rounded-t-lg"
                                        src="{{ $item->item_photo_url }}"
                                        alt="{{ $item->item_name }}" />
                                    <span class="absolute top-1 right-1 bg-white/90 dark:bg-gray-800/90 rounded-full p-1 shadow-sm">
                                        <img src="{{ asset('img/' . $item->type . '.svg') }}"
                                            class="h-4 w-4" title="@lang('modules.menu.' . $item->type)"
                                            alt="" />
                                    </span>
                                </div>
                                @endif

                                {{-- Content Section --}}
                                <div class="p-2">
                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white min-h-[2.5rem]">
                                        {{ $item->item_name }}
                                    </h5>
                                    @if (!$item->in_stock)
                                        <div class="text-red-500">Out of stock</div>
                                    @else

                                    <div class="mt-1 flex items-center justify-between gap-2">
                                        @if ($item->variations_count == 0)
                                            <span class="text-base font-semibold text-gray-900 dark:text-white">
                                                {{ currency_format($item->price, restaurant()->currency_id) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                                @lang('modules.menu.showVariations')
                                            </span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </label>
                    </li>
                @empty
                    <li class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <p>@lang('messages.noItemAdded')</p>
                        </div>
                    </li>
                @endforelse
                
                {{-- Combo Packs --}}
                @if(isset($comboPacks) && $comboPacks->isNotEmpty())
                    @foreach ($comboPacks as $combo)
                        @php
                            // Ensure combo pack items are loaded
                            $combo->loadMissing('comboPackItems.menuItem');
                        @endphp
                        @if($combo->is_active && $combo->isAvailable())
                            <li class="group relative">
                                <input type="checkbox" id="combo-{{ $combo->id }}" value="combo-{{ $combo->id }}"
                                    wire:key='combo-input-{{ $combo->id }}'
                                    class="hidden peer">
                                <label for="combo-{{ $combo->id }}"
                                    onclick='window.posClient?.queueAddCombo({{ $combo->id }});'
                                    class="block w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 dark:hover:bg-gray-700/30 cursor-pointer relative hover:shadow-md bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 dark:bg-gray-800 dark:border-gray-700 active:scale-95 focus-visible:scale-95 focus-visible:ring-2 focus-visible:ring-skin-base outline-none border border-blue-200 dark:border-blue-700"
                                    tabindex="0">
                                    {{-- Image Section --}}
                                    @if (restaurant() && !restaurant()->hide_menu_item_image_on_pos)
                                    <div class="relative aspect-square hidden md:block">
                                        @if ($combo->image)
                                            <img class="w-full h-full object-cover rounded-t-lg"
                                                src="{{ $combo->combo_image_url }}"
                                                alt="{{ $combo->getTranslation('name', app()->getLocale()) }}" />
                                        @else
                                            <div class="w-full h-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900 flex items-center justify-center rounded-t-lg">
                                                <svg class="w-12 h-12 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <span class="absolute top-1 right-1 bg-blue-500 text-white text-xs font-bold rounded-full px-2 py-0.5 shadow-sm">
                                            COMBO
                                        </span>
                                    </div>
                                    @endif

                                    {{-- Content Section --}}
                                    <div class="p-2 min-w-0">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                                            {{ $combo->getTranslation('name', app()->getLocale()) }}
                                        </h5>
                                        <div class="mt-1">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 line-through">
                                                {{ currency_format($combo->regular_price, restaurant()->currency_id) }}
                                            </div>
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                    {{ currency_format($combo->discounted_price, restaurant()->currency_id) }}
                                                </span>
                                                @if($combo->discount_percent > 0)
                                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                                        {{ number_format($combo->discount_percent, 0) }}% OFF
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 overflow-hidden">
                                            @foreach($combo->comboPackItems->take(3) as $cItem)
                                                <div class="truncate">{{ $cItem->quantity }}× {{ $cItem->menuItem?->item_name ?? '?' }}</div>
                                            @endforeach
                                            @if($combo->comboPackItems->count() > 3)
                                                <div class="text-gray-400">+{{ $combo->comboPackItems->count() - 3 }} more</div>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            </li>
                        @endif
                    @endforeach
                @endif
                </ul>
            </div>
        </div>
    </div>
</div>
