<div>
    <!-- Header -->
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 mb-4">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('inventory::modules.transfers.title') }}</h1>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:p-6">
        <!-- Action Button -->
        <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4 mb-6">
            <div class="flex gap-2">
                <x-secondary-button wire:click="export" wire:loading.attr="disabled">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    {{ __('app.export') }}
                </x-secondary-button>
            @if(user_can('Create Stock Transfer'))
                <x-button
                    wire:click="$set('showModal', true)"
                    class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('inventory::modules.transfers.create_transfer') }}
                </x-button>
            @endif
            </div>
        </div>

        <!-- Filters -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col lg:flex-row flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('inventory::modules.transfers.search') }}
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                       placeholder="{{ __('inventory::modules.transfers.search_placeholder') }}">
            </div>
            
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('inventory::modules.transfers.filter_type') }}
                </label>
                <select wire:model.live="filterType" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <option value="all">{{ __('inventory::modules.transfers.all_transfers') }}</option>
                    <option value="outgoing">{{ __('inventory::modules.transfers.outgoing') }}</option>
                    <option value="incoming">{{ __('inventory::modules.transfers.incoming') }}</option>
                </select>
            </div>
            @if($showAdminView)
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('app.branch') }}
                </label>
                <select wire:model.live="branchFilter" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <option value="">{{ trans('app.all') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('inventory::modules.transfers.status') }}
                </label>
                <select wire:model.live="statusFilter" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <option value="all">{{ __('inventory::modules.transfers.all_status') }}</option>
                    <option value="pending">{{ __('inventory::modules.transfers.pending') }}</option>
                    <option value="in_transit">{{ __('inventory::modules.transfers.in_transit') }}</option>
                    <option value="completed">{{ __('inventory::modules.transfers.completed') }}</option>
                    <option value="cancelled">{{ __('inventory::modules.transfers.cancelled') }}</option>
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('app.date') }}
                </label>
                <div class="flex items-center gap-2">
                     <x-input type="date" wire:model.live="startDate" class="block w-full sm:w-auto" />
                     <span class="text-gray-500 font-medium">@lang('app.to')</span>
                     <x-input type="date" wire:model.live="endDate" class="block w-full sm:w-auto" />
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

            @if($search || $startDate || $endDate || $filterType !== 'all' || $statusFilter !== 'all')
                <button
                    wire:click="clearFilters"
                    class="mb-1 inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('inventory::modules.stock.clearFilters') }}
                </button>
            @endif
    </div>

    <!-- Transfers Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('inventory::modules.transfers.transfer_number') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('inventory::modules.transfers.from_to') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('inventory::modules.transfers.items_count') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('inventory::modules.transfers.status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('inventory::modules.transfers.created_at') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('inventory::modules.transfers.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transfers as $transfer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $transfer->transfer_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <div>
                                        {{ $transfer->sourceLocation ? $transfer->sourceLocation->name : $transfer->sourceBranch->name }}
                                        @if($transfer->sourceLocation && $transfer->sourceLocation->type !== 'branch')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-1">
                                                {{ ucfirst($transfer->sourceLocation->type) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        → {{ $transfer->destinationLocation ? $transfer->destinationLocation->name : $transfer->destinationBranch->name }}
                                        @if($transfer->destinationLocation && $transfer->destinationLocation->type !== 'branch')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-1">
                                                {{ ucfirst($transfer->destinationLocation->type) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $transfer->items->count() }} {{ __('inventory::modules.transfers.items') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span @class([
                                    'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' => $transfer->status === 'pending',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' => $transfer->status === 'in_transit',
                                    'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' => $transfer->status === 'completed',
                                    'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' => $transfer->status === 'cancelled',
                                ])>
                                    {{ __('inventory::modules.transfers.status_' . $transfer->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $transfer->created_at->timezone(timezone())->translatedFormat('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @if(user_can('Show Stock Transfer'))
                                    <button wire:click="viewTransfer({{ $transfer->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="{{ __('inventory::modules.transfers.view_transfer') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    @endif
                                    
                                    @if($transfer->status === 'pending' && user_can('Update Stock Transfer'))
                                        <button wire:click="openEditModal({{ $transfer->id }})" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400" title="{{ __('inventory::modules.transfers.edit_transfer') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    @if($transfer->status === 'pending' && user_can('Update Stock Transfer'))
                                        <button wire:click="confirmInitiate({{ $transfer->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400" title="{{ __('inventory::modules.transfers.initiate_transfer') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    @if($transfer->status === 'in_transit' && user_can('Update Stock Transfer'))
                                        <button wire:click="openReceiveModal({{ $transfer->id }})" class="text-purple-600 hover:text-purple-900 dark:text-purple-400" title="{{ __('inventory::modules.transfers.receive_transfer') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    @if(user_can('Cancel Stock Transfer') && in_array($transfer->status, ['pending', 'in_transit']))
                                        <button wire:click="confirmCancel({{ $transfer->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400" title="{{ __('inventory::modules.transfers.cancel_transfer') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.transfers.no_transfers_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $transfers->links() }}
        </div>
    </div>
    </div>

    <!-- View Modal -->
    <x-right-modal wire:model.live="showViewModal">
        <x-slot name="title">
            {{ __('inventory::modules.transfers.view_transfer') }}
        </x-slot>
        <x-slot name="content">
            @if($selectedTransfer)
                @include('inventory::livewire.stock-transfer.partials.view-transfer-details')
            @endif
        </x-slot>
    </x-right-modal>

    <!-- Receive Modal -->
    <x-right-modal wire:model.live="showReceiveModal">
        <x-slot name="title">
            {{ __('inventory::modules.transfers.receive_transfer') }}
        </x-slot>
        <x-slot name="content">
            @if($selectedTransfer)
                <livewire:inventory::stock-transfer.receive-stock-transfer
                    :transfer="$selectedTransfer"
                    wire:key="receive-transfer-{{ $receiveModalKey }}" />
            @endif
        </x-slot>
    </x-right-modal>

    <!-- Create Transfer Modal -->
    @if($showModal)
        <x-right-modal wire:model.live="showModal">
            <x-slot name="title">
                {{ __('inventory::modules.transfers.create_transfer') }}
            </x-slot>
            <x-slot name="content">
                <livewire:inventory::stock-transfer.create-stock-transfer />
            </x-slot>
        </x-right-modal>
    @endif

    <!-- Initiate Transfer Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingInitiation">
        <x-slot name="title">
            {{ __('inventory::modules.transfers.initiate_transfer') }}
        </x-slot>

        <x-slot name="content">
            {{ __('inventory::modules.transfers.confirm_initiate') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmingInitiation', false)" wire:loading.attr="disabled">
                {{ __('app.no') }}
            </x-secondary-button>
                <x-button class="ml-3 bg-green-600 hover:bg-green-700" wire:click="initiateTransfer({{ $selectedTransferForInitiation }})" wire:loading.attr="disabled">
                    {{ __('inventory::modules.transfers.initiate') }}
                </x-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Cancel Transfer Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingCancellation">
        <x-slot name="title">
            {{ __('inventory::modules.transfers.cancel_transfer') }}
        </x-slot>

        <x-slot name="content">
            {{ __('inventory::modules.transfers.confirm_cancel') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmingCancellation', false)" wire:loading.attr="disabled">
                {{ __('inventory::modules.transfers.no_keep_transfer') }}
            </x-secondary-button>

            @if($selectedTransferForCancellation)
                <x-button class="ml-3 bg-red-600 hover:bg-red-700" wire:click="cancelTransfer({{ $selectedTransferForCancellation }})" wire:loading.attr="disabled">
                    {{ __('inventory::modules.transfers.yes_cancel_transfer') }}
                </x-button>
            @endif
        </x-slot>
    </x-confirmation-modal>

    <!-- Edit Transfer Modal -->
    @if($showEditModal && $editTransferId)
        <x-right-modal wire:model.live="showEditModal">
            <x-slot name="title">
                {{ __('inventory::modules.transfers.edit_transfer') }}
            </x-slot>
            <x-slot name="content">
                <livewire:inventory::stock-transfer.edit-stock-transfer
                    :transfer="\Modules\Inventory\Entities\InventoryTransfer::findOrFail($editTransferId)"
                    wire:key="edit-transfer-{{ $editTransferId }}" />
            </x-slot>
        </x-right-modal>
    @endif
</div>

