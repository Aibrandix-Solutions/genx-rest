@extends('layouts.app')

@section('content')

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 sm:p-8">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                    @lang('modules.combo.comboPackSettings')
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    @lang('modules.combo.comboPackSettingsDescription')
                </p>
                
                @livewire('menu.combo-pack-settings')
            </div>
        </div>
    </div>
</div>

@endsection

