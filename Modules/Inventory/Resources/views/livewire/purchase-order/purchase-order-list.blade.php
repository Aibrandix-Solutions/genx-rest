<div class="p-4">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Total Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('inventory::modules.purchaseOrder.total_orders') }}</h3>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_orders'] }}</p>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('inventory::modules.purchaseOrder.pending_orders') }}</h3>
                    <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_orders'] }}</p>
                </div>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('inventory::modules.purchaseOrder.completed_orders') }}</h3>
                    <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $stats['completed_orders'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col lg:flex-row flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('inventory::modules.purchaseOrder.search_placeholder') }}
                </label>
                <x-input type="text" wire:model.live.debounce.300ms="search" 
                       class="block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                       placeholder="{{ trans('inventory::modules.purchaseOrder.search_placeholder') }}" />
            </div>
            @if($showAdminView)
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('app.branch') }}
                </label>
                <x-select wire:model.live="branchFilter" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ trans('app.all') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </x-select>
            </div>
            @endif
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('inventory::modules.purchaseOrder.supplier') }}
                </label>
                <x-select wire:model.live="supplierId" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ trans('inventory::modules.purchaseOrder.all_suppliers') }}</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('inventory::modules.purchaseOrder.status_label') }}
                </label>
                <x-select wire:model.live="status" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ trans('inventory::modules.purchaseOrder.all_status') }}</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('app.date') }}
                </label>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                     <x-input type="date" wire:model.live="startDate" class="block w-full sm:w-auto min-w-[140px]" />
                     <span class="text-gray-500 font-medium text-center">@lang('app.to')</span>
                     <x-input type="date" wire:model.live="endDate" class="block w-full sm:w-auto min-w-[140px]" />
                </div>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('app.perPage') }}
                </label>
                <x-dropdown align="left">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 bg-white dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
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

            @if($search || $startDate || $endDate || $supplierId || $status)
                <div>
                    <x-secondary-button wire:click="clearFilters" class="mb-1">
                        {{ trans('inventory::modules.purchaseOrder.clear_filters') }}
                    </x-secondary-button>
                </div>
            @endif
        </div>
       
    </div>

    <div class="mb-6 flex justify-end gap-2">
        <x-secondary-button wire:click="export" wire:loading.attr="disabled">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            {{ trans('app.export') }}
        </x-secondary-button>
        @if(user_can('Create Purchase Order'))
            <a href="{{ route('purchases.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-skin-base border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-skin-base/90 focus:outline-none focus:border-skin-base focus:ring ring-skin-base/30 disabled:opacity-25 transition ease-in-out duration-150">
                {{ trans('inventory::modules.purchaseOrder.create_title') }}
            </a>
        @endif
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.po_number') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.supplier') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.order_date') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.expected_delivery_date') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.total_amount') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.due_amount') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('app.status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.payment_status_label') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ trans('inventory::modules.purchaseOrder.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($purchaseOrders as $purchaseOrder)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $purchaseOrder->po_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <a href="{{ route('suppliers.show', $purchaseOrder->supplier->id) }}" class="underline underline-offset-1" wire:navigate>
                                    {{ $purchaseOrder->supplier->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $purchaseOrder->order_date->translatedFormat('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $purchaseOrder->expected_delivery_date?->translatedFormat('M d, Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">
                                {{ currency_format($purchaseOrder->total_amount, restaurant()->currency_id) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold {{ $purchaseOrder->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ currency_format($purchaseOrder->due_amount, restaurant()->currency_id) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $purchaseOrder->status === 'draft' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}
                                    {{ $purchaseOrder->status === 'sent' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : '' }}
                                    {{ $purchaseOrder->status === 'received' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}
                                    {{ $purchaseOrder->status === 'partially_received' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : '' }}
                                    {{ $purchaseOrder->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300' : '' }}">
                                    {{ $statuses[$purchaseOrder->status] ?? ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $paymentStatus = $purchaseOrder->payment_status;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $paymentStatus === 'paid' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}
                                    {{ $paymentStatus === 'partial' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : '' }}
                                    {{ $paymentStatus === 'due' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300' : '' }}">
                                    @if($paymentStatus === 'paid')
                                        {{ trans('inventory::modules.purchaseOrder.payment_status.paid') }}
                                    @elseif($paymentStatus === 'partial')
                                        {{ trans('inventory::modules.purchaseOrder.payment_status.partial') }}
                                    @else
                                        {{ trans('inventory::modules.purchaseOrder.payment_status.due') }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open"
                                            @click.away="open = false"
                                            class="inline-flex items-center justify-center w-8 h-8 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full focus:outline-none relative">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                        <div x-show="open"
                                             x-transition
                                             class="absolute right-0 z-50 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5"
                                         x-cloak
                                         @click.away="open = false">
                                        <div class="py-1 flex flex-col gap-1">
                                            @if($purchaseOrder->status === 'draft' && user_can('Update Purchase Order'))
                                                <button wire:click="confirmSend({{ $purchaseOrder->id }})" @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/50">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                    </svg>
                                                    <span>{{ trans('inventory::modules.purchaseOrder.send') }}</span>
                                                </button>
                                            @endif
                                            
                                            @if(!in_array($purchaseOrder->status, ['cancelled']) && user_can('Update Purchase Order') && ($purchaseOrder->status !== 'received' || user_can('Edit Received Purchase')))
                                                <a href="{{ route('purchases.edit', $purchaseOrder->id) }}" @click="open = false" wire:navigate
                                                        class="w-full flex items-center px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/50">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span>{{ trans('inventory::modules.purchaseOrder.edit') }}</span>
                                                </a>
                                            @endif
                                            
                                            @if(in_array($purchaseOrder->status, ['sent', 'partially_received']) && user_can('Update Purchase Order'))
                                                <button wire:click="$dispatch('showReceiveModal', { purchaseOrder: {{ $purchaseOrder->id }} })"
                                                        class="inline-flex items-center px-4 py-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/50 rounded-lg">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                    </svg>
                                                    <span>{{ trans('inventory::modules.purchaseOrder.receive') }}</span>
                                                </button>
                                            @endif

                                            @if($purchaseOrder->due_amount > 0 && user_can('Create Purchase Order'))
                                                <button wire:click="$dispatch('recordPayment', { purchaseId: {{ $purchaseOrder->id }} })" @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/50">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>Record Payment</span>
                                                </button>
                                            @endif

                                            @if(user_can('Show Purchase Order'))
                                                <button wire:click="$dispatch('viewPurchaseOrder', { purchaseOrder: {{ $purchaseOrder->id }} })"
                                                        class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/50 rounded-lg">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>{{ trans('inventory::modules.purchaseOrder.view') }}</span>
                                                </button>
                                            @endif

                                            @if(user_can('Show Purchase Order'))
                                            <button wire:click="downloadPdf({{ $purchaseOrder->id }})"
                                                    class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/50 rounded-lg">
                                                <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                    <span>{{ trans('inventory::modules.purchaseOrder.download_pdf') }}</span>
                                                </button>
                                            @endif

                                            @if(in_array($purchaseOrder->status, ['draft', 'sent']) && user_can('Update Purchase Order'))
                                                <button wire:click="confirmCancel({{ $purchaseOrder->id }})"
                                                        class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/50 rounded-lg">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>{{ trans('app.cancel') }}</span>
                                                </button>
                                            @endif


                                            @if(!in_array($purchaseOrder->status, ['received', 'cancelled']) && user_can('Delete Purchase Order'))
                                                <button wire:click="confirmDelete({{ $purchaseOrder->id }})"
                                                        class="inline-flex items-center px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50 rounded-lg">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>{{ trans('inventory::modules.purchaseOrder.delete') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ trans('inventory::modules.purchaseOrder.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $purchaseOrders->links() }}
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            {{ trans('inventory::modules.purchaseOrder.delete_title') }}
        </x-slot>

        <x-slot name="content">
            {{ trans('inventory::modules.purchaseOrder.delete_confirm') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmingDeletion', false)" wire:loading.attr="disabled">
                {{ trans('inventory::modules.purchaseOrder.cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete" wire:loading.attr="disabled">
                {{ trans('inventory::modules.purchaseOrder.delete') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Send Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingSend">
        <x-slot name="title">
            {{ trans('inventory::modules.purchaseOrder.send_title') }}
        </x-slot>

        <x-slot name="content">
            {{ trans('inventory::modules.purchaseOrder.send_confirm') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmingSend', false)" wire:loading.attr="disabled">
                {{ trans('app.cancel') }}
            </x-secondary-button>

            <x-button class="ml-3" wire:click="send" wire:loading.attr="disabled">
                {{ trans('inventory::modules.purchaseOrder.send') }}
            </x-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Cancel Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingCancel">
        <x-slot name="title">
            {{ trans('inventory::modules.purchaseOrder.cancel_title') }}
        </x-slot>

        <x-slot name="content">
            {{ trans('inventory::modules.purchaseOrder.cancel_confirm') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmingCancel', false)" wire:loading.attr="disabled">
                {{ trans('app.cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="cancel" wire:loading.attr="disabled">
                {{ trans('inventory::modules.purchaseOrder.cancel') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <livewire:inventory::purchase-order.manage-purchase-order />
    <livewire:inventory::purchase-order.receive-purchase-order />
    <livewire:inventory::purchase-order.view-purchase-order />
    <livewire:inventory::purchase-order.purchase-order-payment />

    <!-- Payment Recording Modal -->
    @if($showPaymentModal && $purchaseIdForPayment)
        <x-dialog-modal wire:model="showPaymentModal">
            <x-slot name="title">
                {{ trans('inventory::modules.payments.record_payment') }}
            </x-slot>

            <x-slot name="content">
                <livewire:inventory::record-purchase-payment :purchaseId="$purchaseIdForPayment" :key="'payment-'.$purchaseIdForPayment" />
            </x-slot>
        </x-dialog-modal>
    @endif
</div>